<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Scene;
use App\Services\ElevenLabsService;
use App\Services\OpenAIService;
use App\Services\SceneMediaGenerationService;
use Mockery;
use Tests\TestCase;

class SceneMediaGenerationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_generate_image_delegates_to_openai_service(): void
    {
        $scene = new Scene;
        $project = new Project;
        $project->id = 42;

        $openAI = Mockery::mock(OpenAIService::class);
        $openAI->shouldReceive('generateSceneImage')
            ->once()
            ->with('Prompt', 'scene-file', 42)
            ->andReturn('scenes/42/scene-file.png');

        $elevenLabs = Mockery::mock(ElevenLabsService::class);

        $result = (new SceneMediaGenerationService($openAI, $elevenLabs))
            ->generateImage($scene, $project, 'scene-file', 'Prompt');

        $this->assertSame('scenes/42/scene-file.png', $result);
    }

    public function test_generate_voice_uses_elevenlabs_when_configured(): void
    {
        config(['kathaai.voice_provider' => 'elevenlabs']);

        $scene = new Scene;
        $project = new Project;
        $project->id = 7;

        $openAI = Mockery::mock(OpenAIService::class);
        $elevenLabs = Mockery::mock(ElevenLabsService::class);
        $elevenLabs->shouldReceive('isConfigured')->once()->andReturnTrue();
        $elevenLabs->shouldReceive('textToSpeechWithTiming')
            ->once()
            ->with('Dialogue', 'voice-file', 7)
            ->andReturn([
                'audio_path' => 'scenes/7/voice-file.mp3',
                'caption_path' => 'scenes/7/voice-file.srt',
            ]);

        $result = (new SceneMediaGenerationService($openAI, $elevenLabs))
            ->generateVoice($scene, $project, 'voice-file', 'Dialogue');

        $this->assertSame('elevenlabs', $result['provider']);
        $this->assertSame('scenes/7/voice-file.mp3', $result['voice_url']);
        $this->assertSame('scenes/7/voice-file.srt', $result['caption_url']);
    }

    public function test_generate_voice_falls_back_to_openai_when_configured_provider_is_openai(): void
    {
        config(['kathaai.voice_provider' => 'openai']);

        $scene = new Scene;
        $project = new Project;
        $project->id = 9;

        $openAI = Mockery::mock(OpenAIService::class);
        $openAI->shouldReceive('generateSceneVoice')
            ->once()
            ->with('Narration', 'voice-file', 9)
            ->andReturn('scenes/9/voice-file.mp3');

        $elevenLabs = Mockery::mock(ElevenLabsService::class);
        $elevenLabs->shouldNotReceive('isConfigured');

        $result = (new SceneMediaGenerationService($openAI, $elevenLabs))
            ->generateVoice($scene, $project, 'voice-file', 'Narration');

        $this->assertSame('openai', $result['provider']);
        $this->assertSame('scenes/9/voice-file.mp3', $result['voice_url']);
        $this->assertNull($result['caption_url']);
    }
}
