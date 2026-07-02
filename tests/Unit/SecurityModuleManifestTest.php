<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SecurityModuleManifestTest extends TestCase
{
    public function test_security_module_declares_routes_permissions_and_migrations(): void
    {
        $manifest = require dirname(__DIR__, 2) . '/modules/Security/manifest.php';

        self::assertIsArray($manifest);
        self::assertSame('routes/http.php', $manifest['routes']['http']);
        self::assertSame(['security.view' => 'View Security Risk Report'], $manifest['permissions']);
        self::assertContains('database/migrations/2026_07_02_000001_insert_security_permissions.php', $manifest['migrations']);
        self::assertSame(['security.view'], $manifest['menu']['permissions']);
    }

    public function test_declared_permissions_are_migrated(): void
    {
        $manifest = require dirname(__DIR__, 2) . '/modules/Security/manifest.php';
        $migration = file_get_contents(dirname(__DIR__, 2) . '/modules/Security/database/migrations/2026_07_02_000001_insert_security_permissions.php');

        self::assertIsString($migration);

        foreach (array_keys($manifest['permissions']) as $permission) {
            self::assertStringContainsString($permission, $migration);
        }
    }
}
