<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\InsufficientCreditsException;
use PHPUnit\Framework\TestCase;

class InsufficientCreditsExceptionTest extends TestCase
{
    public function test_exception_stores_required_and_available(): void
    {
        $e = new InsufficientCreditsException(100, 25, 'Custom message.');
        $this->assertSame(100, $e->required);
        $this->assertSame(25, $e->available);
        $this->assertSame('Custom message.', $e->getMessage());
    }

    public function test_exception_extends_base_exception(): void
    {
        $e = new InsufficientCreditsException(10, 5);
        $this->assertInstanceOf(\Exception::class, $e);
    }

    public function test_default_message_includes_required_and_available(): void
    {
        $e = new InsufficientCreditsException(50, 10, '');
        $this->assertStringContainsString('50', $e->getMessage());
        $this->assertStringContainsString('10', $e->getMessage());
    }
}
