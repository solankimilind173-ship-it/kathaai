<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scene extends Model
{
    protected $fillable = ['episode_id', 'title', 'description', 'location', 'time_of_day', 'mood', 'scene_number'];

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }

    public function characters()
    {
        return $this->belongsToMany(Character::class)
            ->withPivot('action')
            ->withTimestamps();
    }
}
