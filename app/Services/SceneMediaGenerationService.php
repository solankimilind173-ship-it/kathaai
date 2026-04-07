<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Scene;

class SceneMediaGenerationService
{
    public function __construct(
        protected OpenAIService $openAI,
        protected ElevenLabsService $elevenLabs
    ) {}

    public function generateImage(Scene $scene, Project $project, string $filename, string $prompt): string
    {
        return $this->openAI->generateSceneImage($prompt, $filename, $project->id);
    }

    /**
     * @return array{voice_url: string, caption_url: string|null, provider: string}
     */
    public function generateVoice(Scene $scene, Project $project, string $filename, string $text): array
    {
        $voiceProvider = config('kathaai.voice_provider', 'openai');
        $useElevenLabs = $voiceProvider === 'elevenlabs' && $this->elevenLabs->isConfigured();

        if ($useElevenLabs) {
            try {
                $result = $this->elevenLabs->textToSpeechWithTiming($text, $filename, $project->id);

                return [
                    'voice_url' => $result['audio_path'],
                    'caption_url' => $result['caption_path'] ?? null,
                    'provider' => 'elevenlabs',
                ];
            } catch (\Throwable) {
                $voicePath = $this->elevenLabs->textToSpeech($text, $filename, $project->id);

                return [
                    'voice_url' => $voicePath,
                    'caption_url' => null,
                    'provider' => 'elevenlabs',
                ];
            }
        }

        return [
            'voice_url' => $this->openAI->generateSceneVoice($text, $filename, $project->id),
            'caption_url' => null,
            'provider' => 'openai',
        ];
    }
}
