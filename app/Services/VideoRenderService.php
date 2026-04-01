<?php

namespace App\Services;

use App\Models\Project;
use App\Models\RenderLog;
use App\Models\Scene;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class VideoRenderService
{
    private const DEFAULT_SCENE_DURATION = 5;

    /** Resolved FFmpeg binary path (set after ensureFfmpegAvailable()). */
    private ?string $resolvedFfmpegPath = null;

    private const DIMENSIONS = [
        '1080p' => ['16:9' => [1920, 1080], '9:16' => [1080, 1920]],
        '4k' => ['16:9' => [3840, 2160], '9:16' => [2160, 3840]],
    ];

    /**
     * Render project scenes into a single video file.
     * Uses FFmpeg to compose image+audio segments, concat, optionally watermark, and capture thumbnail.
     *
     * @return array{output_url: string, thumbnail_url: string|null}
     *
     * @throws \RuntimeException
     */
    public function render(Project $project, RenderLog $renderLog, array $options = []): array
    {
        $resolution = $options['resolution'] ?? '1080p';
        $format = $options['format'] ?? '16:9';
        $fps = (int) ($options['fps'] ?? 24);
        $applyWatermark = (bool) ($options['apply_watermark'] ?? true);
        $useKenBurns = (bool) ($options['ken_burns'] ?? config('kathaai.ken_burns', true));
        $videoProvider = $options['video_provider'] ?? config('kathaai.video_provider', 'ffmpeg');

        [$width, $height] = $this->dimensions($resolution, $format);
        $ratio = $format === '9:16' ? config('services.runway.ratio_9_16', '720:1280') : config('services.runway.ratio_16_9', '1280:720');

        $scenes = $this->getOrderedScenesWithDuration($project);
        if (empty($scenes)) {
            throw new \RuntimeException('Project has no scenes to render. Add episodes and scenes first.');
        }

        $trailer = (bool) ($options['trailer'] ?? false);
        if ($trailer) {
            $scenes = $this->getTrailerSceneList($scenes, 60);
            if (empty($scenes)) {
                throw new \RuntimeException('Trailer rendering requires at least one valid scene and total project duration > 60 minutes.');
            }
        }

        $this->ensureFfmpegAvailable();

        $tempDir = storage_path('app/renders-temp/'.uniqid('render_'.$renderLog->id.'_', true));
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true)) {
            throw new \RuntimeException('Could not create temporary render directory.');
        }

        try {
            $segmentPaths = [];
            foreach ($scenes as $index => $sceneData) {
                $segmentPath = $this->createSegment(
                    $tempDir,
                    $index,
                    $sceneData['scene'],
                    $sceneData['duration_seconds'],
                    $width,
                    $height,
                    $fps,
                    $useKenBurns,
                    $videoProvider,
                    $ratio
                );
                if ($segmentPath) {
                    $segmentPaths[] = $segmentPath;
                }
            }

            if (empty($segmentPaths)) {
                Log::error('VideoRenderService: no segments could be created', [
                    'project_id' => $project->id,
                    'render_log_id' => $renderLog->id,
                    'scene_count' => count($scenes),
                    'hint' => 'Ensure FFmpeg is installed and on PATH where the queue worker runs. Check logs for "segment creation failed" or "placeholder failed".',
                ]);
                throw new \RuntimeException('No segments could be created. Ensure FFmpeg is installed and on PATH. See logs for details.');
            }

            $concatPath = $tempDir.'/concat.txt';
            $concatContent = implode("\n", array_map(fn ($p) => "file '".str_replace("'", "'\\''", $p)."'", $segmentPaths));
            file_put_contents($concatPath, $concatContent);

            $rawOutputPath = $tempDir.'/output.mp4';
            $result = Process::run(array_merge(
                [$this->ffmpegPath(), '-y', '-f', 'concat', '-safe', '0', '-i', $concatPath],
                ['-c', 'copy', $rawOutputPath]
            ));

            if (! $result->successful()) {
                Log::error('VideoRenderService: concat failed', [
                    'stderr' => $result->errorOutput(),
                    'stdout' => $result->output(),
                ]);
                throw new \RuntimeException('FFmpeg concat failed: '.$result->errorOutput());
            }

            $finalPath = $rawOutputPath;
            if ($applyWatermark && class_exists(VideoWatermarkService::class)) {
                $watermark = app(VideoWatermarkService::class)->applyWatermark($rawOutputPath);
                if ($watermark) {
                    $finalPath = $watermark;
                }
            }

            $storageDir = "renders/{$project->id}";
            Storage::disk('public')->makeDirectory($storageDir);

            $videoFilename = "{$renderLog->id}.mp4";
            $videoRelPath = "{$storageDir}/{$videoFilename}";
            $fullDestPath = Storage::disk('public')->path($videoRelPath);
            if (! copy($finalPath, $fullDestPath)) {
                throw new \RuntimeException('Failed to copy final video to storage.');
            }

            $thumbnailRelPath = null;
            $thumbPath = $tempDir.'/thumb.jpg';
            $thumbResult = Process::run([
                $this->ffmpegPath(), '-y', '-i', $fullDestPath, '-ss', '00:00:01', '-vframes', '1', '-q:v', '2', $thumbPath,
            ]);
            if ($thumbResult->successful() && is_file($thumbPath)) {
                $thumbFilename = "{$renderLog->id}_thumb.jpg";
                $thumbRelPath = "{$storageDir}/{$thumbFilename}";
                $thumbDestPath = Storage::disk('public')->path($thumbRelPath);
                if (copy($thumbPath, $thumbDestPath)) {
                    $thumbnailRelPath = $thumbRelPath;
                }
            }

            return [
                'output_url' => $videoRelPath,
                'thumbnail_url' => $thumbnailRelPath,
            ];
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Build scene list for trailer duration cap (seconds).
     *
     * @param  array<int, array{scene: Scene, duration_seconds: int}>  $scenes
     */
    private function getTrailerSceneList(array $scenes, int $maxSeconds = 60): array
    {
        $result = [];
        $total = 0;

        foreach ($scenes as $item) {
            if ($total >= $maxSeconds) {
                break;
            }

            $remaining = $maxSeconds - $total;
            $duration = min($item['duration_seconds'], $remaining);
            if ($duration <= 0) {
                continue;
            }

            $result[] = [
                'scene' => $item['scene'],
                'duration_seconds' => $duration,
            ];
            $total += $duration;
        }

        return $result;
    }

    private function ffmpegPath(): string
    {
        if ($this->resolvedFfmpegPath !== null) {
            return $this->resolvedFfmpegPath;
        }

        return FfmpegPathResolver::resolve();
    }

    private function ensureFfmpegAvailable(): void
    {
        $this->resolvedFfmpegPath = FfmpegPathResolver::resolve();
    }

    /**
     * @return array<int, array{scene: Scene, duration_seconds: int}>
     */
    private function getOrderedScenesWithDuration(Project $project): array
    {
        $project->load(['episodes' => fn ($q) => $q->orderBy('episode_number'), 'episodes.scenes' => fn ($q) => $q->orderBy('scene_number')]);
        $sceneSettings = $project->sceneRenderSettings()->orderBy('sort_order')->get()->keyBy('scene_id');

        $allScenes = $project->episodes->flatMap->scenes->sortBy([
            fn ($a, $b) => ($a->episode->episode_number ?? 0) <=> ($b->episode->episode_number ?? 0),
            fn ($a, $b) => $a->scene_number <=> $b->scene_number,
        ])->values();

        $savedOrder = $sceneSettings->pluck('scene_id')->unique()->values()->all();
        $orderedSceneIds = ! empty($savedOrder)
            ? array_values(array_unique(array_merge($savedOrder, $allScenes->pluck('id')->diff($savedOrder)->values()->all())))
            : $allScenes->pluck('id')->all();

        $sceneMap = $allScenes->keyBy('id');
        $result = [];
        foreach ($orderedSceneIds as $sceneId) {
            $scene = $sceneMap->get($sceneId);
            if (! $scene) {
                continue;
            }
            $settings = $sceneSettings->get($sceneId);
            $duration = $settings?->duration_trimmed ?? $scene->duration ?? self::DEFAULT_SCENE_DURATION;
            $duration = max(1, (int) $duration);
            $result[] = ['scene' => $scene, 'duration_seconds' => $duration];
        }

        return $result;
    }

    private function createSegment(string $tempDir, int $index, Scene $scene, int $durationSeconds, int $width, int $height, int $fps, bool $useKenBurns = true, string $videoProvider = 'ffmpeg', string $ratio = '1280:720'): ?string
    {
        $segmentPath = $tempDir.'/seg_'.sprintf('%04d', $index).'.mp4';
        $audioPath = $this->resolveSceneAudioPath($scene, $durationSeconds, $tempDir, $index);

        if ($videoProvider === 'runway' && $this->runwayService()->isConfigured()) {
            $runwayPath = $this->createSegmentWithRunway($tempDir, $index, $scene, $durationSeconds, $width, $height, $ratio, $audioPath, $segmentPath);
            if ($runwayPath) {
                return $runwayPath;
            }
            Log::warning('VideoRenderService: Runway segment failed, falling back to FFmpeg', [
                'scene_id' => $scene->id,
                'index' => $index,
            ]);
        }

        $imagePath = $this->resolveSceneImagePath($scene, $width, $height, $tempDir, $index);

        if (! $imagePath || ! is_file($imagePath)) {
            $imagePath = $this->createPlaceholderImage($tempDir, $index, $width, $height);
        }
        if (! $imagePath) {
            Log::warning('VideoRenderService: no image for segment (scene image missing and placeholder failed)', [
                'scene_id' => $scene->id,
                'index' => $index,
                'scene_has_image_url' => ! empty($scene->image_url),
            ]);

            return null;
        }

        $filter = $useKenBurns
            ? $this->buildKenBurnsFilter($width, $height, $fps)
            : "scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,fps={$fps}";

        $args = [$this->ffmpegPath(), '-y', '-loop', '1', '-i', $imagePath];
        if ($audioPath && is_file($audioPath)) {
            $args[] = '-i';
            $args[] = $audioPath;
        } else {
            $args[] = '-f';
            $args[] = 'lavfi';
            $args[] = '-i';
            $args[] = 'anullsrc=r=44100:cl=stereo';
        }
        $args = array_merge($args, [
            '-t', (string) $durationSeconds,
            '-vf', $filter,
            '-c:v', 'libx264', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-shortest',
            $segmentPath,
        ]);
        $result = Process::run($args);

        if (! $result->successful() && $useKenBurns) {
            Log::warning('VideoRenderService: Ken Burns segment failed, falling back to static', [
                'scene_id' => $scene->id,
                'index' => $index,
                'stderr' => $result->errorOutput(),
            ]);

            return $this->createSegment($tempDir, $index, $scene, $durationSeconds, $width, $height, $fps, false, $videoProvider, $ratio);
        }

        if (! $result->successful()) {
            Log::warning('VideoRenderService: segment creation failed', [
                'scene_id' => $scene->id,
                'index' => $index,
                'stderr' => $result->errorOutput(),
            ]);

            return null;
        }

        return $segmentPath;
    }

    private function runwayService(): RunwayService
    {
        return app(RunwayService::class);
    }

    /**
     * Generate a segment using Runway image-to-video, then mux with scene audio. Returns segment path or null.
     */
    private function createSegmentWithRunway(string $tempDir, int $index, Scene $scene, int $durationSeconds, int $width, int $height, string $ratio, ?string $audioPath, string $segmentPath): ?string
    {
        $imagePath = $this->resolveSceneImagePath($scene, $width, $height, $tempDir, $index);
        if (! $imagePath || ! is_file($imagePath)) {
            return null;
        }

        $path = $this->storagePathFromUrl($scene->image_url);
        if (! $path) {
            return null;
        }
        $storagePath = str_starts_with($path, 'storage/') ? $path : 'storage/'.$path;
        $imageUrl = rtrim(config('app.url'), '/').'/'.ltrim($storagePath, '/');
        $promptText = mb_substr(trim($scene->description ?? ''), 0, 1000);
        if ($promptText === '') {
            $promptText = 'Scene with subtle motion.';
        }

        $runwayDuration = min(10, max(2, $durationSeconds));
        $runway = $this->runwayService();
        $runwayVideoPath = $runway->imageToVideo($imageUrl, $promptText, $runwayDuration, $ratio);
        if (! $runwayVideoPath || ! is_file($runwayVideoPath)) {
            return null;
        }

        try {
            $padSeconds = max(0, $durationSeconds - $runwayDuration);
            $args = [
                $this->ffmpegPath(), '-y',
                '-i', $runwayVideoPath,
            ];
            if ($audioPath && is_file($audioPath)) {
                $args[] = '-i';
                $args[] = $audioPath;
            } else {
                $args[] = '-f';
                $args[] = 'lavfi';
                $args[] = '-i';
                $args[] = 'anullsrc=r=44100:cl=stereo';
            }
            $scaleFilter = "scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2";
            if ($padSeconds > 0) {
                $args[] = '-filter_complex';
                $args[] = "[0:v]tpad=stop_mode=clone:stop_duration={$padSeconds},{$scaleFilter}[v]";
                $args[] = '-map';
                $args[] = '[v]';
                $args[] = '-map';
                $args[] = '1:a';
            } else {
                $args[] = '-filter_complex';
                $args[] = "[0:v]{$scaleFilter}[v]";
                $args[] = '-map';
                $args[] = '[v]';
                $args[] = '-map';
                $args[] = '1:a';
            }
            $args = array_merge($args, [
                '-t', (string) $durationSeconds,
                '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-c:a', 'aac',
                $segmentPath,
            ]);
            $result = Process::run($args);
            if (! $result->successful()) {
                Log::warning('VideoRenderService: Runway segment mux failed', [
                    'scene_id' => $scene->id,
                    'stderr' => $result->errorOutput(),
                ]);

                return null;
            }

            return $segmentPath;
        } finally {
            @unlink($runwayVideoPath);
        }
    }

    /**
     * Build FFmpeg filter for subtle Ken Burns (zoom) effect.
     * Uses d=1 so zoompan outputs one frame per input frame; segment length is controlled by -t in createSegment().
     */
    private function buildKenBurnsFilter(int $width, int $height, int $fps): string
    {
        $zoomExpr = 'min(zoom+0.001,1.15)';
        $xExpr = 'iw/2-(iw/zoom/2)';
        $yExpr = 'ih/2-(ih/zoom/2)';
        $scalePad = "scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2";
        $zoompan = "zoompan=z='{$zoomExpr}':d=1:x='{$xExpr}':y='{$yExpr}':s={$width}x{$height}:fps={$fps}";

        return "{$scalePad},{$zoompan}";
    }

    private function resolveSceneImagePath(Scene $scene, int $width, int $height, string $tempDir, int $index): ?string
    {
        $url = $scene->image_url;
        if (empty($url)) {
            return null;
        }
        $path = $this->storagePathFromUrl($url);
        if ($path === null) {
            return null;
        }
        $fullPath = Storage::disk('public')->path($path);

        return is_file($fullPath) ? $fullPath : null;
    }

    private function resolveSceneAudioPath(Scene $scene, int $durationSeconds, string $tempDir, int $index): ?string
    {
        $url = $scene->voice_url;
        if (empty($url)) {
            return null;
        }
        $path = $this->storagePathFromUrl($url);
        if ($path === null) {
            return null;
        }
        $fullPath = Storage::disk('public')->path($path);

        return is_file($fullPath) ? $fullPath : null;
    }

    private function storagePathFromUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        if (str_starts_with($url, 'http')) {
            return null;
        }

        return str_starts_with($url, 'storage/') ? substr($url, 8) : $url;
    }

    private function createPlaceholderImage(string $tempDir, int $index, int $width, int $height): ?string
    {
        $path = $tempDir.'/placeholder_'.$index.'.png';
        $result = Process::run([
            $this->ffmpegPath(), '-y', '-f', 'lavfi', '-i', "color=c=#1c1917:s={$width}x{$height}:d=1", '-frames:v', '1', $path,
        ]);

        if (! $result->successful() || ! is_file($path)) {
            Log::warning('VideoRenderService: placeholder image creation failed (FFmpeg may be missing or failed)', [
                'index' => $index,
                'width' => $width,
                'height' => $height,
                'temp_dir' => $tempDir,
                'exit_code' => $result->exitCode(),
                'stderr' => $result->errorOutput(),
                'stdout' => $result->output(),
            ]);

            return null;
        }

        return $path;
    }

    private function dimensions(string $resolution, string $format): array
    {
        $res = self::DIMENSIONS[$resolution] ?? self::DIMENSIONS['1080p'];
        $dims = $res[$format] ?? $res['16:9'];

        return $dims;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($dir);
    }
}
