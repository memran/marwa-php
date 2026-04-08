<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use function debug;
use PHPUnit\Framework\TestCase;

final class DebugHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_DEBUG');
        unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);

        parent::tearDown();
    }

    public function testDebugHelperFollowsAppDebugOnly(): void
    {
        $_ENV['APP_DEBUG'] = '1';
        $_SERVER['APP_DEBUG'] = '1';
        putenv('APP_DEBUG=1');

        self::assertTrue(debug());

        $_ENV['APP_DEBUG'] = '0';
        $_SERVER['APP_DEBUG'] = '0';
        putenv('APP_DEBUG=0');

        self::assertFalse(debug());
    }
}
