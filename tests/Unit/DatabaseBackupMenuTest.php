<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DatabaseBackupMenuTest extends TestCase
{
    public function test_database_backup_manifest_declares_the_admin_menu_item(): void
    {
        $manifest = require dirname(__DIR__, 2) . '/modules/DatabaseBackup/manifest.php';

        self::assertIsArray($manifest);
        self::assertArrayHasKey('menu', $manifest);
        self::assertSame('Administration', $manifest['menu']['section']);
        self::assertSame('Backup & Restore', $manifest['menu']['label']);
        self::assertSame('/admin/database-backups', $manifest['menu']['route']);
        self::assertSame(40, $manifest['menu']['order']);
        self::assertSame('database-zap', $manifest['menu']['icon']);
        self::assertSame(['database_backup.view'], $manifest['menu']['permissions']);
    }
}
