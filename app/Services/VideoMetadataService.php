<?php

namespace App\Services;

use App\Models\Project;

class VideoMetadataService
{
    public const HASHTAG_COUNT = 10;

    public function __construct(
        protected OpenAIService $openAI
    ) {}

    /**
     * Generate video title, description, and exactly 10 hashtags for sharing (e.g. Instagram/YouTube).
     *
     * @return array{title: string, description: string, hashtags: string}
     */
    public function generateForProject(Project $project): array
    {
        $title = $project->title;
        $summary = $this->getProjectSummary($project);

        try {
            $result = $this->openAI->generateVideoMetadata($title, $summary);

            return [
                'title' => $result['title'] ?? $title,
                'description' => $result['description'] ?? $this->fallbackDescription($title, $summary),
                'hashtags' => $this->normalizeHashtags($result['hashtags'] ?? []),
            ];
        } catch (\Throwable $e) {
            return [
                'title' => $title,
                'description' => $this->fallbackDescription($title, $summary),
                'hashtags' => $this->fallbackHashtags($title),
            ];
        }
    }

    private function getProjectSummary(Project $project): string
    {
        $episodes = $project->episodes()->orderBy('episode_number')->limit(3)->get(['title', 'summary']);
        if ($episodes->isEmpty()) {
            return '';
        }

        return $episodes->map(fn ($ep) => ($ep->title ?: 'Episode').': '.($ep->summary ?? ''))->implode("\n");
    }

    private function fallbackDescription(string $title, string $summary): string
    {
        $intro = "Watch \"{$title}\" — a story brought to life with AI-powered video.";
        if ($summary !== '') {
            $intro .= "\n\n".\Illuminate\Support\Str::limit($summary, 300);
        }

        return $intro."\n\nCreated with KathaAI.";
    }

    private function fallbackHashtags(string $title): string
    {
        $words = preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $base = array_unique(array_map(fn ($w) => '#'.preg_replace('/[^a-zA-Z0-9]/', '', $w), $words));
        $defaults = ['#KathaAI', '#AIStory', '#Storytelling', '#ShortFilm', '#Video'];
        $combined = array_slice(array_merge($base, $defaults), 0, self::HASHTAG_COUNT);

        return implode(' ', $combined);
    }

    /**
     * @param  array<string>  $hashtags
     */
    private function normalizeHashtags(array $hashtags): string
    {
        $normalized = [];
        foreach (array_slice($hashtags, 0, self::HASHTAG_COUNT) as $tag) {
            $tag = is_string($tag) ? trim($tag) : '';
            if ($tag === '') {
                continue;
            }
            $tag = str_starts_with($tag, '#') ? $tag : '#'.$tag;
            $normalized[] = preg_replace('/[^\p{L}\p{N}_#]/u', '', $tag) ?: $tag;
        }
        $fill = self::HASHTAG_COUNT - count($normalized);
        if ($fill > 0) {
            $defaults = ['#KathaAI', '#AIStory', '#Storytelling', '#ShortFilm', '#Video', '#Creative', '#Content', '#Reels', '#YouTube', '#Viral'];
            foreach ($defaults as $d) {
                if (count($normalized) >= self::HASHTAG_COUNT) {
                    break;
                }
                if (! in_array($d, $normalized, true)) {
                    $normalized[] = $d;
                }
            }
        }

        return implode(' ', array_slice($normalized, 0, self::HASHTAG_COUNT));
    }
}
