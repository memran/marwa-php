<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Notifications\Models\Notification;
use PHPUnit\Framework\TestCase;

final class NotificationsModuleTest extends TestCase
{
    public function test_notification_model_can_be_instantiated(): void
    {
        $notification = Notification::newInstance([
            'id' => 1,
            'user_id' => 1,
            'type' => 'info',
            'title' => 'Test',
            'message' => 'Test message',
            'is_read' => false,
        ], true);

        self::assertEquals('Test', $notification->getAttribute('title'));
        self::assertEquals('info', $notification->getAttribute('type'));
        self::assertSame(0, $notification->getAttribute('is_read'));
    }

    public function test_notification_model_type_constants(): void
    {
        self::assertEquals('info', Notification::TYPE_INFO);
        self::assertEquals('success', Notification::TYPE_SUCCESS);
        self::assertEquals('warning', Notification::TYPE_WARNING);
        self::assertEquals('error', Notification::TYPE_ERROR);
    }

    public function test_notification_model_types_list(): void
    {
        $types = Notification::types();
        self::assertArrayHasKey('info', $types);
        self::assertArrayHasKey('success', $types);
        self::assertArrayHasKey('warning', $types);
        self::assertArrayHasKey('error', $types);
    }

    public function test_notifications_module_manifest_has_migrations(): void
    {
        $manifest = require __DIR__ . '/../../modules/Notifications/manifest.php';
        
        self::assertArrayHasKey('migrations', $manifest);
        self::assertCount(1, $manifest['migrations']);
        self::assertStringContainsString('create_notifications_table', $manifest['migrations'][0]);
    }
}
