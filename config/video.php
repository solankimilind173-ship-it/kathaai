<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg binary path
    |--------------------------------------------------------------------------
    | Path to the ffmpeg executable. Use this when ffmpeg is not on PATH for
    | the queue worker (e.g. on macOS with Homebrew: /opt/homebrew/bin/ffmpeg
    | or /usr/local/bin/ffmpeg). Leave null or omit to use 'ffmpeg' from PATH.
    */
    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    /*
    |--------------------------------------------------------------------------
    | FFmpeg fallback paths
    |--------------------------------------------------------------------------
    | If the primary path fails (e.g. not on PATH), these are tried in order.
    | Useful when the queue worker runs without a full shell PATH (e.g. macOS Homebrew).
    */
    'ffmpeg_fallback_paths' => [
        '/opt/homebrew/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
    ],
];
