<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ModuleManifestTest extends TestCase
{
    public function testAuthUsersAndSettingsModulesExposeDatabaseMigrationPaths(): void
    {
        $auth = require __DIR__ . '/../../modules/Auth/manifest.php';
        $users = require __DIR__ . '/../../modules/Users/manifest.php';
        $settings = require __DIR__ . '/../../modules/Settings/manifest.php';

        self::assertSame('database/migrations', $auth['paths']['database/migrations']);
        self::assertSame('database/seeders', $auth['paths']['database/seeders']);
        self::assertSame('database/migrations', $users['paths']['database/migrations']);
        self::assertSame('database/seeders', $users['paths']['database/seeders']);
        self::assertSame('database/migrations', $settings['paths']['database/migrations']);
        self::assertSame('database/migrations/2026_04_10_000002_create_password_reset_tokens_table.php', $auth['migrations'][0]);
        self::assertSame('database/migrations/2026_04_10_000001_create_users_table.php', $users['migrations'][0]);
        self::assertSame('database/migrations/2026_04_14_000001_create_settings_table.php', $settings['migrations'][0]);
    }
}
