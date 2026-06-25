<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExecutiveThemeAssetContractTest extends TestCase
{
    public function testExecutiveHeadLoadsTheAppCssBundle(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/layouts/admin.twig');
        $head = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/partials/head.twig');

        self::assertIsString($layout);
        self::assertIsString($head);
        self::assertStringContainsString("{% include 'partials/head.twig' %}", $layout);
        self::assertStringContainsString("theme_asset('assets/css/app.css')", $head);
        self::assertStringContainsString("theme_asset('assets/css/variables.css')", $head);
        self::assertStringContainsString("theme_asset('assets/css/layout.css')", $head);
        self::assertStringContainsString("theme_asset('assets/css/components.css')", $head);
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

    public function testExecutiveLayoutsUseSharedPartialsWithoutDuplicateRuntimeTags(): void
    {
        $adminLayout = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/layouts/admin.twig');
        $authLayout = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/layouts/auth.twig');
        $blankLayout = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/layouts/blank.twig');

        self::assertIsString($adminLayout);
        self::assertIsString($authLayout);
        self::assertIsString($blankLayout);

        self::assertStringContainsString("{% include 'partials/head.twig' %}", $adminLayout);
        self::assertStringContainsString("{% include 'partials/scripts.twig' %}", $adminLayout);
        self::assertStringNotContainsString("theme_asset('assets/js/dashboard.js')", $adminLayout);
        self::assertStringNotContainsString("theme_asset('assets/js/alpine.min.js')", $adminLayout);
        self::assertStringNotContainsString("theme_asset('css/app.css')", $adminLayout);

        self::assertStringContainsString("{% include 'partials/head.twig' %}", $authLayout);
        self::assertStringContainsString("{% include 'partials/scripts.twig' %}", $authLayout);
        self::assertStringContainsString('theme-auth', $authLayout);
        self::assertStringContainsString('theme-auth__card', $authLayout);
        self::assertStringNotContainsString('max-w-2xl', $authLayout);

        self::assertStringContainsString("{% include 'partials/head.twig' %}", $blankLayout);
        self::assertStringContainsString("{% include 'partials/scripts.twig' %}", $blankLayout);
        self::assertStringContainsString('document.documentElement.dataset.adminTheme = finalTheme;', $blankLayout);
        self::assertStringContainsString('class="admin-theme executive-theme min-h-screen bg-app-bg text-app-text antialiased"', $blankLayout);
    }

    public function testExecutiveAuthViewsLiveInsideTheThemePackage(): void
    {
        self::assertFileExists(__DIR__ . '/../../resources/views/themes/executive/login.twig');
        self::assertFileExists(__DIR__ . '/../../resources/views/themes/executive/forgot-password.twig');
        self::assertFileExists(__DIR__ . '/../../resources/views/themes/executive/reset-password.twig');
        self::assertFileDoesNotExist(__DIR__ . '/../../resources/views/themes/executive/modules/Auth/login.twig');
        self::assertFileDoesNotExist(__DIR__ . '/../../resources/views/themes/executive/modules/Auth/forgot-password.twig');
        self::assertFileDoesNotExist(__DIR__ . '/../../resources/views/themes/executive/modules/Auth/reset-password.twig');
        self::assertFileDoesNotExist(__DIR__ . '/../../modules/Auth/resources/views/login.twig');
        self::assertFileDoesNotExist(__DIR__ . '/../../modules/Auth/resources/views/forgot-password.twig');
        self::assertFileDoesNotExist(__DIR__ . '/../../modules/Auth/resources/views/reset-password.twig');
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

    public function testExecutivePublicDashboardRuntimeUsesExecutiveAssets(): void
    {
        $dashboardJs = file_get_contents(__DIR__ . '/../../public/themes/executive/assets/js/dashboard.js');

        self::assertIsString($dashboardJs);
        self::assertStringContainsString('/themes/executive/assets/icons/lucide.svg', $dashboardJs);
        self::assertStringNotContainsString('/themes/admin/assets/icons/lucide.svg', $dashboardJs);
    }

    public function testExecutiveThemeOwnsModuleFacingComponents(): void
    {
        $componentsPath = __DIR__ . '/../../resources/views/themes/executive/components';

        foreach ([
            'activity-log.twig',
            'button-styles.twig',
            'form-field.twig',
            'permission-panel.twig',
            'search-bar.twig',
            'status-badge.twig',
            'status-card.twig',
            'tabs.twig',
            'widget.twig',
        ] as $component) {
            self::assertFileExists($componentsPath . '/' . $component);
        }
    }
}
