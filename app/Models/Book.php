<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'description',
        'content',
        'user_id',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Books available in "Choose a book" dropdown: library books (no owner), current user's books, or public books.
     */
    public function scopeForDropdown(Builder $query, ?int $userId = null): Builder
    {
        return $query
            ->where(function (Builder $q) use ($userId) {
                $q->whereNull('user_id'); // library books
                if ($userId !== null) {
                    $q->orWhere('user_id', $userId); // own uploaded books
                    $q->orWhere('is_public', true);   // other users' public books
                }
            })
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->orderBy('title');
    }
}
