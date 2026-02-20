<?php

namespace Tests\Unit\Enums;

use App\Enums\ProjectStatus;
use PHPUnit\Framework\TestCase;

class ProjectStatusTest extends TestCase
{
    public function test_all_cases_have_expected_values(): void
    {
        $this->assertSame('draft', ProjectStatus::Draft->value);
        $this->assertSame('generating', ProjectStatus::Generating->value);
        $this->assertSame('ready', ProjectStatus::Ready->value);
        $this->assertSame('rendering', ProjectStatus::Rendering->value);
        $this->assertSame('completed', ProjectStatus::Completed->value);
        $this->assertSame('failed', ProjectStatus::Failed->value);
        $this->assertSame('archived', ProjectStatus::Archived->value);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertSame('Draft', ProjectStatus::Draft->label());
        $this->assertSame('Generating', ProjectStatus::Generating->label());
        $this->assertSame('Ready', ProjectStatus::Ready->label());
        $this->assertSame('Rendering', ProjectStatus::Rendering->label());
        $this->assertSame('Completed', ProjectStatus::Completed->label());
        $this->assertSame('Failed', ProjectStatus::Failed->label());
        $this->assertSame('Archived', ProjectStatus::Archived->label());
    }

    public function test_values_returns_all_enum_values_as_array(): void
    {
        $values = ProjectStatus::values();
        $this->assertIsArray($values);
        $this->assertContains('draft', $values);
        $this->assertContains('completed', $values);
        $this->assertCount(7, $values);
    }
}
