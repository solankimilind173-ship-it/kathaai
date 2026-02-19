<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'max_projects',
        'max_dubbing_languages',
        'max_reels_per_episode',
        'max_video_minutes',
        'allow_multiple_video_styles',
        'allow_4k',
        'allow_voice_style_selection',
        'allow_background_music',
        'allow_intro_song_generation',
        'monthly_credits',
        'is_active'
    ];
}
