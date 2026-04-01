<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'ai_fake_mode' => env('AI_FAKE_MODE', false),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'yearly_discount_percent' => (int) env('SUBSCRIPTION_YEARLY_DISCOUNT_PERCENT', 20),
        'plans' => [
            'starter' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_STARTER_YEARLY'),
            ],
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY'),
            ],
            'enterprise' => [
                'monthly' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_ENTERPRISE_YEARLY'),
            ],
        ],
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI', env('APP_URL').'/auth/apple/callback'),
        'key_id' => env('APPLE_KEY_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
    ],

    'elevenlabs' => [
        'key' => env('ELEVENLABS_API_KEY'),
        'voice_id' => env('ELEVENLABS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    ],

    'runway' => [
        'key' => env('RUNWAY_API_KEY'),
        'model' => env('RUNWAY_VIDEO_MODEL', 'gen4_turbo'),
        'ratio_16_9' => '1280:720',
        'ratio_9_16' => '720:1280',
    ],

];
