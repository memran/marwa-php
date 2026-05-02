<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BackgroundJobsManifestTest extends TestCase
{
    public function test_it_is_ui_only_and_omits_runtime_migrations(): void
    {
        $manifest = require dirname(__DIR__, 2) . '/modules/BackgroundJobs/manifest.php';

        self::assertIsArray($manifest);
        self::assertArrayNotHasKey('commands', $manifest['paths']);
        self::assertArrayNotHasKey('database/migrations', $manifest['paths']);
        self::assertArrayNotHasKey('tasks', $manifest);
        self::assertArrayHasKey('permissions', $manifest);
        self::assertArrayHasKey('seeders', $manifest);
        self::assertContains('database/seeders/BackgroundJobsPermissionsSeeder.php', $manifest['seeders']);
        self::assertSame('routes/http.php', $manifest['routes']['http']);
        self::assertArrayNotHasKey('migrations', $manifest);
    }
}
