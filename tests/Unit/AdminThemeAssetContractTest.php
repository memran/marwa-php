<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminThemeAssetContractTest extends TestCase
{
    public function testAdminLayoutLoadsTheAppCssBundle(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/layouts/admin.twig');
        $head = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/partials/head.twig');

        self::assertIsString($layout);
        self::assertIsString($head);
        self::assertStringContainsString("{% include 'partials/head.twig' %}", $layout);
        self::assertStringContainsString("theme_asset('css/app.css')", $head);
        self::assertStringContainsString("theme_asset('css/variables.css')", $head);
        self::assertStringContainsString("theme_asset('css/layout.css')", $head);
        self::assertStringContainsString("theme_asset('css/components.css')", $head);
    }

    public function testAdminLayoutsUseSharedHeadAndScriptPartialsWithoutDuplicateRuntimeTags(): void
    {
        $adminLayout = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/layouts/admin.twig');
        $authLayout = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/layouts/auth.twig');
        $blankLayout = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/layouts/blank.twig');

        self::assertIsString($adminLayout);
        self::assertIsString($authLayout);
        self::assertIsString($blankLayout);

        self::assertStringContainsString("{% include 'partials/head.twig' %}", $adminLayout);
        self::assertStringContainsString("{% include 'partials/scripts.twig' %}", $adminLayout);
        self::assertStringNotContainsString("theme_asset('assets/js/alpine.min.js')", $adminLayout);
        self::assertStringNotContainsString("theme_asset('assets/js/dashboard.js')", $adminLayout);

        self::assertStringContainsString("{% include 'partials/head.twig' %}", $authLayout);
        self::assertStringContainsString("{% include 'partials/scripts.twig' %}", $authLayout);

        self::assertStringContainsString("{% include 'partials/head.twig' %}", $blankLayout);
        self::assertStringContainsString("{% include 'partials/scripts.twig' %}", $blankLayout);
        self::assertStringContainsString('document.documentElement.dataset.adminTheme = finalTheme;', $blankLayout);
        self::assertStringContainsString('class="admin-theme min-h-screen bg-app-bg text-app-text antialiased"', $blankLayout);
    }

    public function testAdminBreadcrumbUsesUtilityMarkupThatWorksWithTheLoadedBundle(): void
    {
        $breadcrumb = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/components/breadcrumb.twig');

        self::assertIsString($breadcrumb);
        self::assertStringContainsString('flex flex-wrap items-center gap-2', $breadcrumb);
        self::assertStringContainsString('hover:text-app-text', $breadcrumb);
        self::assertStringContainsString('font-medium text-app-text', $breadcrumb);
        self::assertStringContainsString('<span aria-hidden="true">/</span>', $breadcrumb);
    }

    public function testAdminSourceCssDoesNotKeepRemovedBreadcrumbOrLegacyShellRules(): void
    {
        $componentsCss = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/assets/css/components.css');
        $layoutCss = file_get_contents(__DIR__ . '/../../resources/views/themes/admin/assets/css/layout.css');

        self::assertIsString($componentsCss);
        self::assertIsString($layoutCss);
        self::assertStringNotContainsString('.theme-breadcrumb__list', $componentsCss);
        self::assertStringNotContainsString('.theme-breadcrumb__item::after', $componentsCss);
        self::assertStringNotContainsString('body.theme-admin', $layoutCss);
        self::assertStringNotContainsString('.theme-shell', $layoutCss);
        self::assertStringNotContainsString('.theme-sidebar__brand-link', $layoutCss);
    }
}
