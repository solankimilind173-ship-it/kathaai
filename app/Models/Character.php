<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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

    protected $appends = ['image_url'];

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
     * Full URL for displaying the character image (used in project show, gallery, etc.).
     * Prefers selected image, then legacy image_path. Returns null if no image.
     */
    public function getImageUrlAttribute(): ?string
    {
        $path = null;
        if ($this->relationLoaded('selectedImage') && $this->selectedImage && ! empty($this->selectedImage->image_url)) {
            $path = $this->selectedImage->image_url;
        } elseif (! empty($this->image_path)) {
            $path = $this->image_path;
        }
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
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
