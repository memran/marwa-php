<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Support;

use App\Modules\Notifications\Models\Notification;
use Marwa\DB\Query\Builder;

final class NotificationRepository
{
    public function forUser(int $userId): Builder
    {
        return Notification::newQuery()->getBaseBuilder()
            ->where('user_id', '=', $userId);
    }

    public function latestForUser(int $userId, int $limit = 5): array
    {
        $rows = $this->forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return array_map(
            static fn (array|object $row): Notification => Notification::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }

    public function paginatedForUser(int $userId, int $page = 1, ?int $perPage = null, string $filter = 'all'): array
    {
        $page = max(1, $page);
        $perPage = max(1, (int) ($perPage ?? per_page(15)));

        $builder = $this->forUser($userId);
        $this->applyReadFilter($builder, $filter);

        $pageData = $builder->orderBy('created_at', 'desc')
            ->paginate($perPage, $page);

        $pageData['data'] = array_map(
            static fn (array|object $row): Notification => Notification::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $pageData['data']
        );

        return $pageData;
    }

    public function unreadCountForUser(int $userId): int
    {
        return (int) $this->forUser($userId)
            ->where('is_read', '=', 0)
            ->count();
    }

    public function findById(int $notificationId, int $userId): ?Notification
    {
        $row = $this->forUser($userId)
            ->where('id', '=', $notificationId)
            ->first();

        return $row === null ? null : Notification::newInstance(
            is_array($row) ? $row : (array) $row,
            true
        );
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->findById($notificationId, $userId);

        if ($notification === null) {
            return false;
        }

        return $notification->markAsRead();
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->forUser($userId)
            ->where('is_read', '=', 0)
            ->update([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function delete(int $notificationId, int $userId): bool
    {
        $notification = $this->findById($notificationId, $userId);

        if ($notification === null) {
            return false;
        }

        return $notification->delete();
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    private function applyReadFilter(Builder $builder, string $filter): void
    {
        if ($filter === 'unread') {
            $builder->where('is_read', '=', 0);
            return;
        }

        if ($filter === 'read') {
            $builder->where('is_read', '=', 1);
        }
    }
}
