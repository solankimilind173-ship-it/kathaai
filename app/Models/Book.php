<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'description',
        'content',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
