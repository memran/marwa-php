<?php

declare(strict_types=1);

namespace Tests\Feature;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class WelcomePageTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['marwa_app'] = new Application(dirname(__DIR__, 2));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);

        parent::tearDown();
    }

    public function testWelcomePageIncludesTheHeroCopyAndAnimationHooks(): void
    {
        $html = view()->render('welcome');

        self::assertStringContainsString('Build with clarity, ship with structure.', $html);
        self::assertStringContainsString('Framework-first PHP starter', $html);
        self::assertStringContainsString('Framework + modules + views', $html);
        self::assertStringContainsString('animate-fade-up', $html);
        self::assertStringContainsString('Boot flow', $html);
        self::assertStringContainsString('Architecture strip', $html);
    }
}
