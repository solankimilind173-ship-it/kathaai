<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'direction',
        'is_active',
        'is_ai_supported',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_ai_supported' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // One language can belong to many projects
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Recommended for SaaS filtering)
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAiSupported($query)
    {
        return $query->where('is_ai_supported', true);
    }
    public function dubbedProjects()
    {
        return $this->belongsToMany(
            Project::class,
            'project_dub_languages'
        );
    }
}
