<?php

namespace App\Services;

class StoryChunker
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function chunk(string $story, int $maxLength = 1800): array
    {
        // Clean multiple spaces
        $story = preg_replace('/\s+/', ' ', trim($story));

        // Split by sentences
        $sentences = preg_split('/(?<=[.?!])\s+/', $story);

        $chunks = [];
        $currentChunk = '';

        foreach ($sentences as $sentence) {

            if (strlen($currentChunk.' '.$sentence) > $maxLength) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ' '.$sentence;
            }
        }

        if (! empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
