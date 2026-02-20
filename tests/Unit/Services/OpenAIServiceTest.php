<?php

namespace Tests\Unit\Services;

use App\Services\OpenAIService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeResponseObject(string $content): object
    {
        return (object) [
            'choices' => [
                (object) [
                    'message' => (object) ['content' => $content],
                ],
            ],
        ];
    }

    public function test_generate_episodes_returns_decoded_json_array(): void
    {
        $json = '[{"title":"Ep 1","summary":"Summary 1"},{"title":"Ep 2","summary":"Summary 2"}]';
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return isset($arg['model'], $arg['messages']) && $arg['model'] === 'gpt-4o-mini';
            }))
            ->andReturn($this->makeResponseObject($json));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->generateEpisodes('Once upon a time...');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('Ep 1', $result[0]['title']);
        $this->assertSame('Summary 2', $result[1]['summary']);
    }

    public function test_generate_episodes_throws_when_empty_choices(): void
    {
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')->andReturn((object) ['choices' => []]);
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI returned no response');
        $service->generateEpisodes('Story');
    }

    public function test_extract_characters_returns_characters_array(): void
    {
        $json = '{"characters":[{"name":"Kiran","description":"brave boy"},{"name":"Bhairav","description":"wise elder"}]}';
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')
            ->once()
            ->andReturn($this->makeResponseObject($json));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->extractCharacters('Story text');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('Kiran', $result[0]['name']);
        $this->assertSame('wise elder', $result[1]['description']);
    }

    public function test_extract_characters_returns_empty_array_when_no_characters_key(): void
    {
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')->andReturn($this->makeResponseObject('{}'));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->extractCharacters('Story');

        $this->assertSame([], $result);
    }

    public function test_generate_character_prompt_returns_content_string(): void
    {
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return str_contains($arg['messages'][1]['content'] ?? '', 'Character: Kiran');
            }))
            ->andReturn($this->makeResponseObject('A young boy with short black hair, wearing traditional clothes.'));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->generateCharacterPrompt('Kiran', 'brave messenger');

        $this->assertSame('A young boy with short black hair, wearing traditional clothes.', $result);
    }

    public function test_generate_scenes_returns_decoded_array(): void
    {
        $json = '[{"scene_number":1,"title":"Opening","location":"Desert","description":"Sun rises"}]';
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')->once()->andReturn($this->makeResponseObject($json));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->generateScenes('Episode summary');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['scene_number']);
        $this->assertSame('Opening', $result[0]['title']);
    }

    public function test_generate_scenes_returns_empty_array_when_content_empty(): void
    {
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')->andReturn($this->makeResponseObject(''));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->generateScenes('Summary');

        $this->assertSame([], $result);
    }

    public function test_map_scene_characters_returns_array(): void
    {
        $json = '[{"name":"Kiran","action":"running"},{"name":"Bhairav","action":"watching"}]';
        $mockChat = Mockery::mock();
        $mockChat->shouldReceive('create')->once()->andReturn($this->makeResponseObject($json));
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('chat')->andReturn($mockChat);

        $service = new OpenAIService($mockClient);
        $result = $service->mapSceneCharacters('Scene text', [['name' => 'Kiran'], ['name' => 'Bhairav']]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('Kiran', $result[0]['name']);
    }

    public function test_generate_character_image_returns_stored_path_on_success(): void
    {
        $fakeImageContent = 'fake-binary-image-data';
        $b64 = base64_encode($fakeImageContent);
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => $b64]],
            ], 200),
        ]);

        $mockClient = Mockery::mock();
        $service = new OpenAIService($mockClient);
        $path = $service->generateCharacterImage('A brave boy', 'kiran', 1);

        $this->assertSame('characters/1/kiran.png', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($fakeImageContent, Storage::disk('public')->get($path));
    }
}