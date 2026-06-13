<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ThemeBuildScriptTest extends TestCase
{
    public function testPackageJsonBuildScriptsIncludeExecutiveTheme(): void
    {
        $package = json_decode((string) file_get_contents(__DIR__ . '/../../package.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($package);
        self::assertArrayHasKey('scripts', $package);
        self::assertSame('npm run css:build', $package['scripts']['build']);
        self::assertSame(
            'npm run css:build:default && npm run css:build:admin && npm run css:build:executive',
            $package['scripts']['css:build']
        );
        self::assertArrayHasKey('css:build:executive', $package['scripts']);
        self::assertArrayHasKey('css:dev:executive', $package['scripts']);
        self::assertArrayHasKey('icons:build:executive', $package['scripts']);
    }
}
