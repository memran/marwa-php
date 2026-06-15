<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Theme\ThemeValidator;
use PHPUnit\Framework\TestCase;

final class ExecutiveThemePackageTest extends TestCase
{
    public function testExecutiveThemeManifestUsesTheStandardPackageFormat(): void
    {
        $manifest = require __DIR__ . '/../../resources/views/themes/executive/manifest.php';

        self::assertIsArray($manifest);
        self::assertSame('executive', $manifest['name']);
        self::assertSame('executive', $manifest['slug']);
        self::assertSame('1.0.0', $manifest['version']);
        self::assertSame('admin', $manifest['type']);
        self::assertArrayHasKey('layouts', $manifest);
        self::assertArrayHasKey('assets', $manifest);
        self::assertSame('layouts/admin.twig', $manifest['layouts']['admin']);
        self::assertSame('layouts/auth.twig', $manifest['layouts']['auth']);
        self::assertSame('layouts/blank.twig', $manifest['layouts']['blank']);
    }

    public function testExecutiveThemePassesThemeValidation(): void
    {
        $result = (new ThemeValidator())->validate('executive');

        self::assertTrue($result->isValid(), implode(PHP_EOL, $result->errors()));
        self::assertSame('Executive', $result->displayName());
    }

    public function testExecutiveAuthLayoutUsesTheSplitScreenShell(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/layouts/auth.twig');

        self::assertIsString($layout);
        self::assertStringContainsString('Built for teams that ship.', $layout);
        self::assertStringContainsString('marwa-admin-theme', $layout);
        self::assertStringContainsString('lg:grid-cols-[1.05fr_0.95fr]', $layout);
    }

    public function testExecutiveHeadLoadsTheInterFont(): void
    {
        $head = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/partials/head.twig');

        self::assertIsString($head);
        self::assertStringContainsString('fonts.googleapis.com', $head);
        self::assertStringContainsString('Inter:wght@400;500;600;700;800', $head);
    }

    public function testExecutiveSidebarUsesTheNewExecutiveBranding(): void
    {
        $sidebar = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/partials/sidebar.twig');

        self::assertIsString($sidebar);
        self::assertStringContainsString('MARWA-PHP', $sidebar);
        self::assertStringContainsString('theme-sidebar__brand-panel', $sidebar);
        self::assertStringContainsString('theme-sidebar__link', $sidebar);
        self::assertStringNotContainsString('theme-sidebar__footer', $sidebar);
        self::assertStringContainsString('bg-[#1E3A8A]', $sidebar);
    }

    public function testExecutiveCardSupportsLegacyEmbedBlocks(): void
    {
        $card = file_get_contents(__DIR__ . '/../../resources/views/themes/executive/components/card.twig');

        self::assertIsString($card);
        self::assertStringContainsString('block header', $card);
        self::assertStringContainsString('block body', $card);
        self::assertStringContainsString('block footer', $card);
    }
}
