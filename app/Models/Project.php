<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'language',
        'status',
    ];

    public function chunks()
    {
        return $this->hasMany(StoryChunk::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }
}
