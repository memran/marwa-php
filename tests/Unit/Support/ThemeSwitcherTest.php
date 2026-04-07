<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ThemeSwitcher;
use PHPUnit\Framework\TestCase;

final class ThemeSwitcherTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('FRONTEND_THEME');
        putenv('ADMIN_THEME');
        unset($_ENV['FRONTEND_THEME'], $_SERVER['FRONTEND_THEME']);
        unset($_ENV['ADMIN_THEME'], $_SERVER['ADMIN_THEME']);
        parent::tearDown();
    }

    public function testResolveFallsBackToDefaultForUnknownThemes(): void
    {
        $switcher = new ThemeSwitcher();

        self::assertSame('default', $switcher->resolve('unknown'));
        self::assertSame(['default', 'dark', 'admin'], $switcher->availableThemes());
    }

    public function testThemeForRequestUsesTheFallbackWhenPreviewIsMissing(): void
    {
        $switcher = new ThemeSwitcher();

        self::assertSame('default', $switcher->themeForRequest('default', 'dark', null));
    }

    public function testThemeForRequestUsesThePreviewThemeWhenRequested(): void
    {
        $switcher = new ThemeSwitcher();

        self::assertSame('dark', $switcher->themeForRequest('default', 'dark', '1'));
    }

    public function testThemeForRequestFallsBackWhenPreviewThemeIsInvalid(): void
    {
        $switcher = new ThemeSwitcher();

        self::assertSame('default', $switcher->themeForRequest('default', 'tenantA', '1'));
    }

    public function testConfiguredFrontendThemeBecomesTheDefaultTheme(): void
    {
        $_ENV['FRONTEND_THEME'] = 'dark';
        $_SERVER['FRONTEND_THEME'] = 'dark';
        putenv('FRONTEND_THEME=dark');

        $switcher = new ThemeSwitcher();

        self::assertSame('dark', $switcher->frontendTheme());
        self::assertContains('dark', $switcher->availableThemes());
        self::assertContains('admin', $switcher->availableThemes());
    }

    public function testConfiguredAdminThemeCanBeResolved(): void
    {
        $_ENV['ADMIN_THEME'] = 'admin';
        $_SERVER['ADMIN_THEME'] = 'admin';
        putenv('ADMIN_THEME=admin');

        $switcher = new ThemeSwitcher();

        self::assertSame('admin', $switcher->adminTheme());
    }
}
