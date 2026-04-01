<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ElevenLabsService
{
    private const BASE_URL = 'https://api.elevenlabs.io/v1';

    public function isConfigured(): bool
    {
        return ! empty(config('services.elevenlabs.key'));
    }

    /**
     * Convert text to speech and save as MP3. Returns relative path for use in scene.voice_url.
     */
    public function textToSpeech(string $text, string $filename, int|string $projectId, ?string $voiceId = null, ?string $modelId = null): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Text for TTS cannot be empty.');
        }
        $input = mb_substr($trimmed, 0, 4096);

        $voiceId = $voiceId ?? config('services.elevenlabs.voice_id', '21m00Tcm4TlvDq8ikWAM');
        $modelId = $modelId ?? config('services.elevenlabs.model_id', 'eleven_multilingual_v2');

        $url = self::BASE_URL.'/text-to-speech/'.$voiceId.'?output_format=mp3_44100_128';

        $response = Http::timeout(120)
            ->connectTimeout(30)
            ->retry(2, 3000)
            ->withHeaders([
                'xi-api-key' => config('services.elevenlabs.key'),
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg',
            ])
            ->post($url, [
                'text' => $input,
                'model_id' => $modelId,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('ElevenLabs TTS failed: '.$response->body());
        }

        $directory = "scenes/{$projectId}";
        $path = "{$directory}/{$filename}.mp3";
        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    /**
     * Convert text to speech with word-level timestamps for captions. Returns ['audio_path' => string, 'caption_data' => array].
     * Caption data can be used to generate SRT or burn subtitles.
     */
    public function textToSpeechWithTiming(string $text, string $filename, int|string $projectId, ?string $voiceId = null, ?string $modelId = null): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Text for TTS cannot be empty.');
        }
        $input = mb_substr($trimmed, 0, 4096);

        $voiceId = $voiceId ?? config('services.elevenlabs.voice_id', '21m00Tcm4TlvDq8ikWAM');
        $modelId = $modelId ?? config('services.elevenlabs.model_id', 'eleven_multilingual_v2');

        $url = self::BASE_URL.'/text-to-speech/'.$voiceId.'/with-timestamps?output_format=mp3_44100_128';

        $response = Http::timeout(120)
            ->connectTimeout(30)
            ->retry(2, 3000)
            ->withHeaders([
                'xi-api-key' => config('services.elevenlabs.key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($url, [
                'text' => $input,
                'model_id' => $modelId,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('ElevenLabs TTS with timestamps failed: '.$response->body());
        }

        $json = $response->json();
        $audioBase64 = $json['audio_base64'] ?? null;
        $alignment = $json['alignment'] ?? null;

        $directory = "scenes/{$projectId}";
        Storage::disk('public')->makeDirectory($directory);

        $audioPath = "{$directory}/{$filename}.mp3";
        if ($audioBase64) {
            Storage::disk('public')->put($audioPath, base64_decode($audioBase64, true));
        }

        $captionPath = null;
        if (! empty($alignment) && is_array($alignment)) {
            $srt = $this->alignmentToSrt($alignment);
            if ($srt !== '') {
                $captionPath = "{$directory}/{$filename}.srt";
                Storage::disk('public')->put($captionPath, $srt);
            }
        }

        return [
            'audio_path' => $audioPath,
            'caption_path' => $captionPath,
            'alignment' => $alignment,
        ];
    }

    /**
     * Build SRT content from ElevenLabs alignment (characters with start/end in seconds).
     * Handles both start_time_seconds/end_time_seconds and start/end key names.
     */
    private function alignmentToSrt(array $alignment): string
    {
        $characters = $alignment['characters'] ?? $alignment['character_start_times_seconds'] ?? [];
        if (empty($characters) || ! is_array($characters)) {
            return '';
        }

        $lines = [];
        $index = 1;
        $currentPhrase = '';
        $start = null;
        $end = null;
        $phraseMaxChars = 60;

        foreach ($characters as $c) {
            $char = $c['character'] ?? $c['char'] ?? '';
            $s = (float) ($c['start_time_seconds'] ?? $c['start'] ?? 0);
            $e = (float) ($c['end_time_seconds'] ?? $c['end'] ?? 0);
            if ($start === null) {
                $start = $s;
            }
            $end = $e;
            $currentPhrase .= $char;

            if (strlen($currentPhrase) >= $phraseMaxChars || in_array($char, ['.', '!', '?', "\n"], true)) {
                $lines[] = (string) $index;
                $lines[] = $this->secondsToSrtTime($start).' --> '.$this->secondsToSrtTime($end);
                $lines[] = trim($currentPhrase);
                $lines[] = '';
                $index++;
                $currentPhrase = '';
                $start = null;
            }
        }

        if (trim($currentPhrase) !== '' && $start !== null) {
            $lines[] = (string) $index;
            $lines[] = $this->secondsToSrtTime($start).' --> '.$this->secondsToSrtTime($end);
            $lines[] = trim($currentPhrase);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function secondsToSrtTime(float $seconds): string
    {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = (int) floor($seconds % 60);
        $ms = (int) round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d,%03d', $h, $m, $s, $ms);
    }
}
