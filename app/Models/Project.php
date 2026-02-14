<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['user_id','title','language','status','image_generation_completed'];
    public function chunks()
    {
        return $this->hasMany(StoryChunk::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }
}
