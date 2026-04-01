<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class VideoWatermarkService
{
    /**
     * Apply the KathaAI logo as watermark to a video file.
     * Requires FFmpeg to be installed on the system.
     *
     * Example usage when generating video (e.g. in a job):
     *   $watermarked = app(VideoWatermarkService::class)->applyWatermark($videoPath);
     *   if ($watermarked) { ... use $watermarked as final video path ... }
     *
     * @param  string  $inputPath  Full path to the source video file
     * @param  string|null  $outputPath  Full path for watermarked output. If null, writes to temp path (same dir, .watermarked before extension)
     * @return string|null Path to the watermarked video on success, null on failure
     */
    public function applyWatermark(string $inputPath, ?string $outputPath = null): ?string
    {
        $logoPath = config('watermark.logo_path');
        if (! $logoPath || ! is_file($logoPath)) {
            Log::warning('Video watermark: logo file not found', ['path' => $logoPath]);

            return null;
        }

        $outputPath = $outputPath ?? $this->tempOutputPath($inputPath);
        $position = config('watermark.position', 'main_w-overlay_w-20:main_h-overlay_h-20');
        $scale = config('watermark.scale');

        if ($scale !== null && $scale > 0) {
            $filterComplex = "[1:v]scale=iw*{$scale}:-1[logo];[0:v][logo]overlay={$position}";
        } else {
            $filterComplex = "[0:v][1:v]overlay={$position}";
        }

        $ffmpeg = FfmpegPathResolver::resolve();
        $result = Process::run([
            $ffmpeg, '-y',
            '-i', $inputPath,
            '-i', $logoPath,
            '-filter_complex', $filterComplex,
            '-codec:a', 'copy',
            $outputPath,
        ]);

        if (! $result->successful()) {
            Log::error('Video watermark FFmpeg failed', [
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ]);

            return null;
        }

        return $outputPath;
    }

    /**
     * Get a temporary output path for the watermarked video (same dir as input, with .watermarked suffix before extension).
     */
    protected function tempOutputPath(string $inputPath): string
    {
        $pathInfo = pathinfo($inputPath);
        $dir = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $ext = $pathInfo['extension'] ?? 'mp4';

        return $dir.DIRECTORY_SEPARATOR.$filename.'.watermarked.'.$ext;
    }
}
