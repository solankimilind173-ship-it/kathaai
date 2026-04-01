<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunwayService
{
    private const BASE_URL = 'https://api.runwayml.com/v1';

    private const API_VERSION = '2024-11-06';

    private const POLL_INTERVAL_SECONDS = 5;

    private const MAX_POLL_ATTEMPTS = 120;

    public function isConfigured(): bool
    {
        return ! empty(config('services.runway.key'));
    }

    /**
     * Generate video from an image and text prompt. Polls until complete, then downloads the output to a local path.
     * Returns the local file path to the downloaded MP4, or null on failure.
     *
     * @param  string  $imageUrl  HTTPS URL or data URI of the scene image
     * @param  string  $promptText  Description of what should happen in the video (max 1000 chars)
     * @param  int  $durationSeconds  Duration 2-10
     * @param  string  $ratio  e.g. 1280:720 or 720:1280
     * @param  string|null  $model  e.g. gen4_turbo, gen4.5
     */
    public function imageToVideo(string $imageUrl, string $promptText, int $durationSeconds = 5, string $ratio = '1280:720', ?string $model = null): ?string
    {
        $model = $model ?? config('services.runway.model', 'gen4_turbo');
        $durationSeconds = max(2, min(10, (int) $durationSeconds));

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer '.config('services.runway.key'),
                'Content-Type' => 'application/json',
                'X-Runway-Version' => self::API_VERSION,
            ])
            ->post(self::BASE_URL.'/image_to_video', [
                'model' => $model,
                'promptImage' => $imageUrl,
                'promptText' => mb_substr(trim($promptText), 0, 1000),
                'duration' => $durationSeconds,
                'ratio' => $ratio,
            ]);

        if (! $response->successful()) {
            Log::warning('RunwayService: image_to_video request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $taskId = $response->json('id');
        if (! $taskId) {
            Log::warning('RunwayService: no task id in response', ['body' => $response->body()]);

            return null;
        }

        $outputUrl = $this->pollTaskUntilComplete($taskId);
        if (! $outputUrl) {
            return null;
        }

        return $this->downloadToTempFile($outputUrl, $taskId);
    }

    private function pollTaskUntilComplete(string $taskId): ?string
    {
        $attempts = 0;
        while ($attempts < self::MAX_POLL_ATTEMPTS) {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.config('services.runway.key'),
                    'X-Runway-Version' => self::API_VERSION,
                ])
                ->get(self::BASE_URL.'/tasks/'.$taskId);

            if (! $response->successful()) {
                Log::warning('RunwayService: get task failed', ['task_id' => $taskId, 'status' => $response->status()]);

                return null;
            }

            $status = $response->json('status');
            if ($status === 'SUCCEEDED') {
                $output = $response->json('output');
                if (is_array($output) && isset($output[0])) {
                    return $output[0];
                }
                if (is_string($output)) {
                    return $output;
                }
                Log::warning('RunwayService: succeeded but no output URL', ['task_id' => $taskId]);

                return null;
            }

            if (in_array($status, ['FAILED', 'CANCELLED', 'DELETED'], true)) {
                Log::warning('RunwayService: task ended with status', ['task_id' => $taskId, 'status' => $status]);

                return null;
            }

            $attempts++;
            sleep(self::POLL_INTERVAL_SECONDS);
        }

        Log::warning('RunwayService: poll timeout', ['task_id' => $taskId]);

        return null;
    }

    private function downloadToTempFile(string $url, string $taskId): ?string
    {
        $response = Http::timeout(120)->get($url);
        if (! $response->successful()) {
            Log::warning('RunwayService: download failed', ['url' => substr($url, 0, 80)]);

            return null;
        }

        $dir = storage_path('app/runway-temp');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            return null;
        }
        $path = $dir.'/'.$taskId.'.mp4';
        file_put_contents($path, $response->body());

        return $path;
    }
}
