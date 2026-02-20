<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI;

class OpenAIService
{
    protected $client;

    /**
     * @param  \OpenAI\Contracts\ClientContract|null  $client  Optional for testing; defaults to OpenAI::client().
     */
    public function __construct($client = null)
    {
        $this->client = $client ?? OpenAI::client(config('services.openai.key'));
    }

    /**
     * Safely get content from the first choice. Returns null if choices are empty (e.g. content filter, rate limit).
     */
    private function getFirstChoiceContent(object $response): ?string
    {
        if (empty($response->choices) || ! isset($response->choices[0]->message->content)) {
            return null;
        }
        return $response->choices[0]->message->content;
    }

    public function generateEpisodes(string $story): array
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
You are a screenplay planner.

Read the story and break it into exactly 6 episodes.

Return ONLY JSON in this format:

[
 { "title": "Episode title", "summary": "Short episode summary" },
 { "title": "Episode title", "summary": "Short episode summary" }
]

No markdown. No explanation. Only JSON.
'
                ],
                [
                    'role' => 'user',
                    'content' => $story
                ]
            ],
            'temperature' => 0.7,
        ]);

        $content = $this->getFirstChoiceContent($response);
        if ($content === null) {
            throw new \RuntimeException('OpenAI returned no response (empty choices). Try again or check your API key.');
        }
        return json_decode($content, true) ?? [];
    }

    public function extractCharacters(string $story): array
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
Extract the main characters from the story.

Return STRICT JSON in this exact schema:

{
  "characters": [
    {"name":"Kiran","description":"young desert boy, brave messenger"},
    {"name":"Bhairav","description":"elderly watchtower keeper, wise mentor"}
  ]
}

Rules:
- Always include "characters" root key
- Each character must contain name and description
- No markdown
- No explanation
'
                ],
                [
                    'role' => 'user',
                    'content' => $story
                ]
            ],
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object']
        ]);

        $content = $this->getFirstChoiceContent($response);
        if ($content === null) {
            throw new \RuntimeException('OpenAI returned no response (empty choices). Try again or check your API key.');
        }
        $data = json_decode($content, true);

        return $data['characters'] ?? [];
    }

    public function generateCharacterPrompt(string $name, string $description): string
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
You are a cinematic character designer.

Create a detailed visual description for AI image generation.

Include:
- age
- clothing
- body type
- facial features
- hairstyle
- ethnicity (Indian/Asian appropriate)
- cinematic style

Return ONLY the visual description paragraph.
'
                ],
                [
                    'role' => 'user',
                    'content' => "Character: $name. Description: $description"
                ]
            ],
            'temperature' => 0.7
        ]);

        $content = $this->getFirstChoiceContent($response);
        if ($content === null) {
            throw new \RuntimeException('OpenAI returned no response (empty choices). Try again or check your API key.');
        }
        return $content;
    }

    public function generateCharacterImage(string $prompt, string $filename, int|string $projectId): string
    {
        $response = Http::timeout(300) // 5 minutes
            ->connectTimeout(60)
            ->retry(3, 5000)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'gpt-image-1',
                'prompt' => $prompt,
                'size' => '1024x1024'
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        $image_base64 = $response->json('data.0.b64_json');

        $image = base64_decode($image_base64);

        $directory = "characters/{$projectId}";
        $path = "{$directory}/{$filename}.png";

        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->put($path, $image);

        return $path;
    }

    /**
     * @param  array<int, array{name: string, reference: string}>  $lockedFaceReferences  Character name => face reference URL/path for visual consistency
     */
    public function generateScenes(string $episodeSummary, array $lockedFaceReferences = []): array
    {
        $faceRefPrompt = '';
        if (! empty($lockedFaceReferences)) {
            $lines = array_map(
                fn (array $ref) => "- {$ref['name']}: use locked face reference for all visual descriptions",
                $lockedFaceReferences
            );
            $faceRefPrompt = "\n\nAlways use the locked face reference for each character. Maintain visual consistency:\n" . implode("\n", $lines) . "\n";
        }

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
You are a screenplay writer.

Break the episode into cinematic scenes.

Return ONLY valid JSON array.

FORMAT:
[
 {
   "scene_number":1,
   "title":"...",
   "location":"...",
   "time_of_day":"day/night/evening",
   "mood":"...",
   "description":"full cinematic visual description"
 }
]

Rules:
- No markdown
- No explanation
- Only JSON array
' . $faceRefPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $episodeSummary
                ]
            ]
        ]);

        $content = $this->getFirstChoiceContent($response);

        if ($content === null || $content === '') {
            Log::error('AI returned empty scene response');
            return [];
        }

        // VERY IMPORTANT (AI sometimes wraps in ```json```)
        $content = trim($content);
        $content = preg_replace('/^```json|```$/i', '', $content);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Scene JSON decode failed', [
                'error' => json_last_error_msg(),
                'ai_response' => $content
            ]);
            return [];
        }

        return $decoded;
    }

    public function mapSceneCharacters(string $scene, array $characters): array
    {
        $characterList = json_encode($characters);

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
Identify which characters are physically present in the scene.

Return ONLY JSON array.

FORMAT:
[
  {"name":"Kiran","action":"riding a horse through the desert"},
  {"name":"Bhairav","action":"standing on the tower watching horizon"}
]
'
                ],
                [
                    'role' => 'user',
                    'content' => "Scene:\n" . $scene . "\n\nCharacters:\n" . $characterList
                ]
            ],
            'temperature' => 0.1,
        ]);

        $content = $this->getFirstChoiceContent($response);
        if ($content === null) {
            throw new \RuntimeException('OpenAI returned no response (empty choices). Try again or check your API key.');
        }
        return json_decode($content, true) ?? [];
    }
}
