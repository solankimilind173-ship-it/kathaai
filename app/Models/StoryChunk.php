<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryChunk extends Model
{
    protected $fillable = ['project_id','chunk_text','chunk_order','token_count'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
