<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ThemeSwitcher;
use PHPUnit\Framework\TestCase;

final class ThemeSwitcherTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        $_SESSION = [];
        parent::tearDown();
    }

    public function testResolveFallsBackToDefaultForUnknownThemes(): void
    {
        $switcher = new ThemeSwitcher();

        self::assertSame('default', $switcher->resolve('unknown'));
        self::assertSame(['default', 'dark'], $switcher->availableThemes());
    }

    public function testPersistStoresAValidatedThemeNameInTheSession(): void
    {
        $switcher = new ThemeSwitcher();
        $switcher->persist('dark');

        self::assertSame('dark', $_SESSION['theme_name']);
    }

    public function testPersistFallsBackToDefaultWhenThemeIsInvalid(): void
    {
        $switcher = new ThemeSwitcher();
        $switcher->persist('tenantA');

        self::assertSame('default', $_SESSION['theme_name']);
    }
}
