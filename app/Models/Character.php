<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'locked_face',
        'selected_image_id',
        'image_prompt',
        'image_path',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function characterImages(): HasMany
    {
        return $this->hasMany(CharacterImage::class);
    }

    public function selectedImage(): BelongsTo
    {
        return $this->belongsTo(CharacterImage::class, 'selected_image_id');
    }

    /**
     * Reference URL/path to use when generating scenes (locked face for consistency).
     * Prefer locked_face, then selected image, then legacy image_path.
     */
    public function getLockedFaceReference(): ?string
    {
        if (! empty($this->locked_face)) {
            return $this->locked_face;
        }
        if ($this->relationLoaded('selectedImage') && $this->selectedImage) {
            return $this->selectedImage->image_url;
        }
        if (! empty($this->image_path)) {
            return $this->image_path;
        }
        return null;
    }

    public function scenes()
    {
        return $this->belongsToMany(Scene::class)
            ->withPivot('action')
            ->withTimestamps();
    }
}
