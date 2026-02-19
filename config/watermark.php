<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Watermark logo path (for generated videos)
    |--------------------------------------------------------------------------
    | Path relative to storage disk or public path. Used when applying
    | the KathaAI logo as watermark on exported videos.
    */
    'logo_path' => public_path('images/kathaai-logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Watermark position on video
    |--------------------------------------------------------------------------
    | FFmpeg overlay position: main_w, main_h = video dimensions.
    | Examples: "main_w-overlay_w-20:main_h-overlay_h-20" (bottom-right),
    | "20:20" (top-left), "main_w-overlay_w-20:20" (top-right).
    */
    'position' => 'main_w-overlay_w-20:main_h-overlay_h-20',

    /*
    |--------------------------------------------------------------------------
    | Logo scale (optional)
    |--------------------------------------------------------------------------
    | Scale overlay as percentage of video width, e.g. 0.15 = 15% of width.
    | Set to null to use original logo size.
    */
    'scale' => 0.15,
];
