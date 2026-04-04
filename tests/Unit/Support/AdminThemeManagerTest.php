<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Modules\Auth\Support\AdminThemeManager;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class AdminThemeManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testDefaultsToLightMode(): void
    {
        $manager = new AdminThemeManager(new ArraySession());

        self::assertSame('light', $manager->current());
        self::assertFalse($manager->isDark());
        self::assertSame('Dark mode', $manager->label());
    }

    public function testToggleSwitchesBetweenLightAndDarkModes(): void
    {
        $session = new ArraySession();
        $manager = new AdminThemeManager($session);

        self::assertSame('dark', $manager->toggle());
        self::assertSame('dark', $session->get('auth_admin_theme'));
        self::assertTrue($manager->isDark());
        self::assertSame('Light mode', $manager->label());

        self::assertSame('light', $manager->toggle());
        self::assertSame('light', $session->get('auth_admin_theme'));
        self::assertFalse($manager->isDark());
    }
}
