<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Support;

use App\Modules\Notifications\Models\Notification;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $repository,
    ) {}

    public function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): Notification {
        return $this->repository->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'is_read' => 0,
        ]);
    }

    public function sendToAdmins(
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?int $exceptAdminId = null
    ): int {
        $adminIds = $this->getAdminUserIds($exceptAdminId);
        $created = 0;

        foreach ($adminIds as $adminId) {
            $this->send($adminId, $type, $title, $message, $actionUrl);
            $created++;
        }

        return $created;
    }

    public function info(int $userId, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->send($userId, Notification::TYPE_INFO, $title, $message, $actionUrl);
    }

    public function success(int $userId, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->send($userId, Notification::TYPE_SUCCESS, $title, $message, $actionUrl);
    }

    public function warning(int $userId, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->send($userId, Notification::TYPE_WARNING, $title, $message, $actionUrl);
    }

    public function error(int $userId, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->send($userId, Notification::TYPE_ERROR, $title, $message, $actionUrl);
    }

    private function getAdminUserIds(?int $exceptId = null): array
    {
        $builder = \App\Modules\Users\Models\User::newQuery()->getBaseBuilder()
            ->where('role', '=', 'admin')
            ->where('is_active', '=', 1);

        if ($exceptId !== null) {
            $builder->where('id', '!=', $exceptId);
        }

        $rows = $builder->pluck('id');
        $ids = is_array($rows) ? $rows : (array) $rows;

        return array_map('intval', $ids);
    }
}