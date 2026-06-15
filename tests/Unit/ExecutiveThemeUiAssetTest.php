<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExecutiveThemeUiAssetTest extends TestCase
{
    public function testExecutivePublicAppCssUsesTheExecutiveBundleOnly(): void
    {
        $appCss = file_get_contents(__DIR__ . '/../../public/themes/executive/css/app.css');

        self::assertIsString($appCss);
        self::assertStringContainsString('../assets/css/app.css', $appCss);
        self::assertStringNotContainsString('/themes/admin/css/app.css', $appCss);
    }
}
