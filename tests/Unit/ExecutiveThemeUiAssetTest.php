<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExecutiveThemeUiAssetTest extends TestCase
{
    public function testExecutivePublicAppCssUsesTheExecutiveBundleOnly(): void
    {
        $appCss = file_get_contents(__DIR__ . '/../../public/themes/executive/assets/css/app.css');

        self::assertIsString($appCss);
        self::assertStringContainsString('.admin-theme.executive-theme .admin-shell', $appCss);
        self::assertStringContainsString('.admin-theme.executive-theme .admin-sidebar', $appCss);
        self::assertStringNotContainsString('/themes/admin/css/app.css', $appCss);
    }

    public function testExecutiveSourceAppCssBridgesTopbarAndFooterThemeTokens(): void
    {
        $appCss = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/assets/css/app.css');

        self::assertIsString($appCss);
        self::assertStringContainsString('.admin-theme.executive-theme .admin-topbar .bg-slate-100', $appCss);
        self::assertStringContainsString('.admin-theme.executive-theme footer .border-white', $appCss);
        self::assertStringContainsString('.admin-theme.executive-theme .admin-topbar .hover\\:bg-slate-200:hover', $appCss);
        self::assertStringContainsString('.admin-theme.executive-theme .admin-topbar .text-blue-600', $appCss);
    }
}
