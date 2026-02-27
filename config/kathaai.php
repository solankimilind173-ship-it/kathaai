<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-render after pipeline
    |--------------------------------------------------------------------------
    | When true, after character images are generated the pipeline automatically
    | queues one video render (RenderProjectJob) so the user gets a video without
    | manually clicking "Render". Set to false to require manual render only.
    */
    'auto_render_after_pipeline' => env('KATHAAI_AUTO_RENDER_AFTER_PIPELINE', true),

    /*
    |--------------------------------------------------------------------------
    | Maximum auto-renders per project per day
    |--------------------------------------------------------------------------
    | Controls how many times the background pipeline may automatically start a
    | video render for the same project within a single calendar day.
    |
    | For now, values greater than 1 are treated the same as 1. Set to 0 to
    | disable automatic renders entirely and require manual renders.
    */
    'auto_render_max_per_project_per_day' => env('KATHAAI_AUTO_RENDER_MAX_PER_PROJECT_PER_DAY', 1),
];
