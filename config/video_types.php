<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Frame options: aspect ratio shown as small icon with ratio label.
    | Stored as project.video_frame (e.g. 16:9, 9:16).
    |--------------------------------------------------------------------------
    */
    'frames' => [
        ['id' => '16:9', 'label' => '16:9'],
        ['id' => '9:16', 'label' => '9:16'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video types (style): cinematic, realistic, animated, etc.
    | User selects one; stored as project.video_type.
    | Each can have name and sample_image_url for the selection UI.
    |--------------------------------------------------------------------------
    */
    'types' => [
        [
            'id' => 'cinematic',
            'name' => 'Cinematic',
            'description' => 'Film-like look with depth and mood.',
            'sample_image_url' => 'https://placehold.co/120x68/1c1917/white?text=Cinematic&font=roboto',
        ],
        [
            'id' => 'realistic',
            'name' => 'Realistic',
            'description' => 'Natural, lifelike visuals.',
            'sample_image_url' => 'https://placehold.co/120x68/166534/white?text=Realistic&font=roboto',
        ],
        [
            'id' => 'animated',
            'name' => 'Animated',
            'description' => 'Cartoon or illustrated style.',
            'sample_image_url' => 'https://placehold.co/120x68/7c3aed/white?text=Animated&font=roboto',
        ],
        [
            'id' => 'documentary',
            'name' => 'Documentary',
            'description' => 'Clean, factual presentation.',
            'sample_image_url' => 'https://placehold.co/120x68/0f766e/white?text=Doc&font=roboto',
        ],
    ],
];
