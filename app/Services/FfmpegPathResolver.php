<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class FfmpegPathResolver
{
    private static ?string $resolved = null;

    /**
     * Return a working FFmpeg binary path. Tries config path then fallback paths.
     * Caches the result for the rest of the request.
     */
    public static function resolve(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $candidates = array_filter(array_merge(
            [config('video.ffmpeg_path', 'ffmpeg')],
            config('video.ffmpeg_fallback_paths', [])
        ));

        foreach ($candidates as $path) {
            $path = (string) $path;
            if ($path === '') {
                continue;
            }
            $result = Process::run([$path, '-version']);
            if ($result->successful()) {
                self::$resolved = $path;
                return $path;
            }
        }

        $primary = (string) config('video.ffmpeg_path', 'ffmpeg');
        $hint = $primary === 'ffmpeg'
            ? 'Install FFmpeg (e.g. brew install ffmpeg on macOS) or set FFMPEG_PATH in .env to the full path.'
            : "FFmpeg at configured path [{$primary}] failed. Check FFMPEG_PATH in .env.";
        throw new \RuntimeException("FFmpeg is required for video rendering but could not be run. {$hint}");
    }

    /**
     * Clear cached path (e.g. for testing).
     */
    public static function clearCache(): void
    {
        self::$resolved = null;
    }
}
