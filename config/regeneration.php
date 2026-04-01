<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Regeneration rate limits (per day)
    |--------------------------------------------------------------------------
    | Prevents abuse by capping how many times a user can regenerate content
    | in a 24-hour window. Counts are tracked per resource (scene/project).
    */
    'max_scene_image_per_scene_per_day' => (int) env('REGENERATION_MAX_SCENE_IMAGE_PER_SCENE_PER_DAY', 10),
    'max_scene_voice_per_scene_per_day' => (int) env('REGENERATION_MAX_SCENE_VOICE_PER_SCENE_PER_DAY', 10),
    'max_episode_per_project_per_day' => (int) env('REGENERATION_MAX_EPISODE_PER_PROJECT_PER_DAY', 5),
];
