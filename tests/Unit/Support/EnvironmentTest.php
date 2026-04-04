<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Environment;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvironmentTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CODEX_ENV_TEST');
        putenv('CODEX_ENV_BOOL');
        putenv('CODEX_ENV_INT');
        parent::tearDown();
    }

    public function testStringReturnsDefaultWhenUnset(): void
    {
        self::assertSame('fallback', Environment::string('CODEX_ENV_TEST', 'fallback'));
    }

    public function testBoolParsesTruthyValues(): void
    {
        putenv('CODEX_ENV_BOOL=1');

        self::assertTrue(Environment::bool('CODEX_ENV_BOOL'));
    }

    public function testIntegerParsesNumericValues(): void
    {
        putenv('CODEX_ENV_INT=12');

        self::assertSame(12, Environment::integer('CODEX_ENV_INT'));
    }

    public function testRequiredStringThrowsForMissingValues(): void
    {
        $this->expectException(RuntimeException::class);

        Environment::requiredString('CODEX_ENV_TEST');
    }
}
