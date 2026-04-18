<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ModuleManifestTest extends TestCase
{
    public function testAuthUsersAndSettingsModulesExposeDatabaseMigrationPaths(): void
    {
        $auth = require __DIR__ . '/../../modules/Auth/manifest.php';
        $activity = require __DIR__ . '/../../modules/Activity/manifest.php';
        $databaseManager = require __DIR__ . '/../../modules/DatabaseManager/manifest.php';
        $notifications = require __DIR__ . '/../../modules/Notifications/manifest.php';
        $roles = require __DIR__ . '/../../modules/Roles/manifest.php';
        $users = require __DIR__ . '/../../modules/Users/manifest.php';
        $settings = require __DIR__ . '/../../modules/Settings/manifest.php';

        self::assertSame('resources/views', $activity['paths']['views']);
        self::assertSame(['auth'], $activity['requires']);
        self::assertSame('resources/views', $auth['paths']['views']);
        self::assertSame('database/migrations', $auth['paths']['database/migrations']);
        self::assertSame('database/seeders', $auth['paths']['database/seeders']);
        self::assertSame(['auth'], $databaseManager['requires']);
        self::assertSame('resources/views', $databaseManager['paths']['views']);
        self::assertSame(['auth', 'users'], $notifications['requires']);
        self::assertSame(['auth'], $roles['requires']);
        self::assertSame('resources/views', $users['paths']['views']);
        self::assertSame('database/migrations', $users['paths']['database/migrations']);
        self::assertSame('database/seeders', $users['paths']['database/seeders']);
        self::assertSame(['auth', 'activity'], $users['requires']);
        self::assertSame(['auth'], $settings['requires']);
        self::assertSame('database/migrations', $settings['paths']['database/migrations']);
        self::assertSame('database/migrations/2026_04_10_000002_create_password_reset_tokens_table.php', $auth['migrations'][0]);
        self::assertSame('database/migrations/2026_04_10_000001_create_users_table.php', $users['migrations'][0]);
        self::assertSame('database/migrations/2026_04_14_000001_create_settings_table.php', $settings['migrations'][0]);
    }
}
