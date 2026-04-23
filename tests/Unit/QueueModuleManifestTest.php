<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class QueueModuleManifestTest extends TestCase
{
    public function test_it_declares_queue_permissions_and_routes(): void
    {
        $manifest = require dirname(__DIR__, 2) . '/modules/Queue/manifest.php';

        self::assertIsArray($manifest);
        self::assertSame('queue', $manifest['slug']);
        self::assertArrayHasKey('permissions', $manifest);
        self::assertArrayHasKey('queue.view', $manifest['permissions']);
        self::assertArrayHasKey('queue.retry', $manifest['permissions']);
        self::assertArrayHasKey('seeders', $manifest);
        self::assertContains('database/seeders/QueuePermissionsSeeder.php', $manifest['seeders']);
        self::assertSame('routes/http.php', $manifest['routes']['http']);
    }
}
