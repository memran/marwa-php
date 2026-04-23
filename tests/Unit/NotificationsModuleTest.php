<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Notifications\Events\NotificationCreated;
use App\Modules\Notifications\Events\NotificationDeleted;
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
        self::assertCount(2, $manifest['migrations']);
        self::assertStringContainsString('create_notifications_table', $manifest['migrations'][0]);
    }

    public function test_notification_created_event_exposes_payload(): void
    {
        $notification = Notification::newInstance([
            'id' => 11,
            'user_id' => 7,
            'type' => 'success',
            'title' => 'Build complete',
            'message' => 'The build finished successfully.',
            'action_url' => '/admin/notifications/11',
            'is_read' => 0,
        ], true);

        $event = new NotificationCreated($notification);

        self::assertSame('notification.created', $event->getName());
        self::assertSame(11, $event->payload['id']);
        self::assertSame(7, $event->payload['user_id']);
        self::assertSame('success', $event->payload['type']);
        self::assertSame('Build complete', $event->payload['title']);
    }

    public function test_notification_deleted_event_exposes_payload(): void
    {
        $notification = Notification::newInstance([
            'id' => 12,
            'user_id' => 7,
            'type' => 'info',
            'title' => 'Cleanup',
            'message' => 'The item was removed.',
            'action_url' => null,
            'is_read' => 0,
        ], true);

        $event = new NotificationDeleted($notification);

        self::assertSame('notification.deleted', $event->getName());
        self::assertSame(12, $event->payload['id']);
        self::assertSame(7, $event->payload['user_id']);
        self::assertSame('info', $event->payload['type']);
        self::assertSame('Cleanup', $event->payload['title']);
    }
}
