<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = ['project_id', 'name', 'description', 'image_prompt', 'image_path'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function scenes()
    {
        return $this->belongsToMany(Scene::class)
            ->withPivot('action')
            ->withTimestamps();
    }
}
