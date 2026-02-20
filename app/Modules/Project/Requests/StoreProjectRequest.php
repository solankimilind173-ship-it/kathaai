<?php

namespace App\Modules\Project\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', Rule::in(['library', 'uploaded'])],
            'title' => 'required|string|max:255',
            'book_id' => 'required_if:source_type,library|nullable|exists:books,id',
            'story' => 'required_if:source_type,uploaded|nullable|string|max:50000',
            'language_id' => 'nullable|exists:languages,id',
            'dub_languages' => 'nullable|array',
            'dub_languages.*' => 'exists:languages,id',
            'video_minutes' => 'nullable|integer|min:1|max:120',
            'quality' => 'nullable|in:1080p,4k',
            'reels_per_episode' => 'nullable|integer|min:0',
            'intro_song' => 'nullable|boolean',
            'background_music' => 'nullable|boolean',
        ];
    }
}
