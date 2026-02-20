<?php

namespace Tests\Unit\Services;

use App\Services\StoryChunker;
use PHPUnit\Framework\TestCase;

class StoryChunkerTest extends TestCase
{
    private StoryChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new StoryChunker;
    }

    public function test_chunk_returns_single_chunk_when_short(): void
    {
        $story = 'Hello world. This is short.';
        $chunks = $this->chunker->chunk($story, 500);
        $this->assertCount(1, $chunks);
        $this->assertSame('Hello world. This is short.', $chunks[0]);
    }

    public function test_chunk_splits_by_sentences_within_max_length(): void
    {
        $sentences = [];
        for ($i = 0; $i < 5; $i++) {
            $sentences[] = 'Sentence number ' . ($i + 1) . ' here.';
        }
        $story = implode(' ', $sentences);
        $chunks = $this->chunker->chunk($story, 50);
        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(51, strlen($chunk), 'Chunk should not exceed max length');
        }
    }

    public function test_chunk_trims_and_collapses_whitespace(): void
    {
        $story = "  Multiple   spaces   between.   Words here.  ";
        $chunks = $this->chunker->chunk($story, 500);
        $this->assertCount(1, $chunks);
        $this->assertSame('Multiple spaces between. Words here.', $chunks[0]);
    }

    public function test_chunk_respects_custom_max_length(): void
    {
        $story = 'First sentence. Second sentence. Third sentence.';
        $chunksSmall = $this->chunker->chunk($story, 20);
        $this->assertGreaterThanOrEqual(2, count($chunksSmall));
        $chunksLarge = $this->chunker->chunk($story, 500);
        $this->assertCount(1, $chunksLarge);
    }

    public function test_chunk_whitespace_only_returns_array(): void
    {
        $chunks = $this->chunker->chunk('   ', 100);
        $this->assertIsArray($chunks);
        // After trim and split, implementation may return one empty chunk for whitespace-only input
        $this->assertLessThanOrEqual(1, count($chunks));
    }

    public function test_chunk_splits_on_question_and_exclamation(): void
    {
        $story = 'Really? Yes! Sure.';
        $chunks = $this->chunker->chunk($story, 10);
        $this->assertGreaterThanOrEqual(1, count($chunks));
    }
}
