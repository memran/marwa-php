<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Database\Seeders;

use App\Modules\Notifications\Models\Notification;
use App\Modules\Users\Models\User;
use Marwa\DB\Seeder\Seeder;

final class NotificationSeeder implements Seeder
{
    public function run(): void
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return;
        }

        $admin = User::newQuery()->getBaseBuilder()
            ->where('role', '=', 'admin')
            ->where('is_active', '=', 1)
            ->first();

        if ($admin === null) {
            return;
        }

        $adminId = is_array($admin) ? $admin['id'] : $admin->id;
        $existingCount = Notification::newQuery()->getBaseBuilder()
            ->where('user_id', '=', $adminId)
            ->count();

        if ($existingCount > 0) {
            return;
        }

        $notifications = [
            [
                'type' => Notification::TYPE_INFO,
                'title' => 'System initialized',
                'message' => 'The application has started successfully.',
                'is_read' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            ],
            [
                'type' => Notification::TYPE_SUCCESS,
                'title' => 'Database connected',
                'message' => 'Successfully connected to the database.',
                'is_read' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            ],
            [
                'type' => Notification::TYPE_INFO,
                'title' => 'Settings loaded',
                'message' => 'Application settings have been loaded from the database.',
                'is_read' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
            [
                'type' => Notification::TYPE_WARNING,
                'title' => 'Cache warming',
                'message' => 'Cache is being rebuilt. Some operations may be slower than usual.',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'type' => Notification::TYPE_INFO,
                'title' => 'Notifications module ready',
                'message' => 'The notifications system is now active and ready to receive alerts.',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-12 hours')),
            ],
            [
                'type' => Notification::TYPE_SUCCESS,
                'title' => 'Welcome to MarwaPHP',
                'message' => 'Your notification bell is now active. Click to see your latest alerts.',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create(array_merge($notification, [
                'user_id' => $adminId,
                'read_at' => $notification['is_read'] ? date('Y-m-d H:i:s') : null,
            ]));
        }
    }
}