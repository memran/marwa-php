<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExecutiveThemeAssetContractTest extends TestCase
{
    public function testExecutiveHeadLoadsTheAppCssBundle(): void
    {
        $head = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/partials/head.twig');

        self::assertIsString($head);
        self::assertStringContainsString("theme_asset('css/app.css')", $head);
    }

    public function testExecutiveThemeUsesTheExecutivePaletteAndDashboardContract(): void
    {
        $layoutCss = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/assets/css/layout.css');
        $componentsCss = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/assets/css/components.css');
        $appCss = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/assets/css/app.css');

        self::assertIsString($layoutCss);
        self::assertIsString($componentsCss);
        self::assertIsString($appCss);
        self::assertStringContainsString('#071b33', $layoutCss);
        self::assertStringContainsString('#041326', $layoutCss);
        self::assertStringContainsString('#1e3a8a', $componentsCss);
        self::assertStringContainsString('.dashboard-stat-card', $componentsCss);
        self::assertStringContainsString('.dashboard-panel', $componentsCss);
        self::assertStringContainsString('.theme-button--primary', $componentsCss);
        self::assertStringContainsString('248 250 252', $appCss);
        self::assertStringContainsString('37 99 235', $appCss);
    }

    public function testExecutiveScriptsLoadTheAdminShellRuntime(): void
    {
        $scripts = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/partials/scripts.twig');

        self::assertIsString($scripts);
        self::assertStringContainsString("theme_asset('assets/js/dashboard.js')", $scripts);
        self::assertStringContainsString("theme_asset('assets/js/alpine.min.js')", $scripts);
    }

    public function testExecutiveLayoutUsesTheAdminThemeShell(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/layouts/admin.twig');

        self::assertIsString($layout);
        self::assertStringContainsString('x-data="adminTheme()"', $layout);
        self::assertStringContainsString('close-mobile.window', $layout);
        self::assertStringContainsString('lg:grid-cols-[280px_minmax(0,1fr)]', $layout);
        self::assertStringContainsString('h-[72px]', $layout);
    }

    public function testExecutiveLayoutCssDoesNotImportTheAppCssBundleRecursively(): void
    {
        $layoutCss = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/assets/css/layout.css');

        self::assertIsString($layoutCss);
        self::assertStringNotContainsString('/themes/executive/css/app.css', $layoutCss);
    }

    public function testExecutivePublicAdminShellAssetsExist(): void
    {
        self::assertFileExists(__DIR__ . '/../../public/themes/executive/assets/js/dashboard.js');
        self::assertFileExists(__DIR__ . '/../../public/themes/executive/assets/js/alpine.min.js');
    }
}
