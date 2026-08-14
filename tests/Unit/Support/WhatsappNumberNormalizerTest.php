<?php

namespace Tests\Unit\Support;

use App\Support\WhatsappNumberNormalizer;
use PHPUnit\Framework\TestCase;

class WhatsappNumberNormalizerTest extends TestCase
{
    public function test_it_normalizes_supported_indonesian_formats(): void
    {
        $this->assertSame('6281234567890', WhatsappNumberNormalizer::normalize('081234567890'));
        $this->assertSame('6281234567890', WhatsappNumberNormalizer::normalize('6281234567890'));
        $this->assertSame('6281234567890', WhatsappNumberNormalizer::normalize('+62 812-3456-7890'));
    }

    public function test_it_rejects_empty_invalid_and_unsupported_numbers(): void
    {
        foreach (['', 'abc', '08123', '1234567890', '6281234567890123456'] as $value) {
            $this->assertNull(WhatsappNumberNormalizer::normalize($value), $value);
        }
    }

    public function test_it_validates_canonical_values_only(): void
    {
        $this->assertTrue(WhatsappNumberNormalizer::isValid('6281234567890'));
        $this->assertFalse(WhatsappNumberNormalizer::isValid('081234567890'));
        $this->assertFalse(WhatsappNumberNormalizer::isValid('628123'));
    }
}
