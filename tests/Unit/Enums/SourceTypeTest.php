<?php

namespace Tests\Unit\Enums;

use App\Enums\SourceType;
use PHPUnit\Framework\TestCase;

class SourceTypeTest extends TestCase
{
    public function test_all_cases_have_expected_values(): void
    {
        $this->assertSame('library', SourceType::Library->value);
        $this->assertSame('uploaded', SourceType::Uploaded->value);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertSame('Library', SourceType::Library->label());
        $this->assertSame('Uploaded', SourceType::Uploaded->label());
    }
}
