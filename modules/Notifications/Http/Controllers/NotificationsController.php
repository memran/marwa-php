<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Http\Controllers;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Notifications\Models\Notification;
use App\Modules\Notifications\Support\NotificationRepository;
use App\Modules\Notifications\Support\NotificationService;
use App\Modules\Users\Models\User;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly NotificationService $service,
        private readonly AuthManager $auth,
    ) {}

    public function index(): ResponseInterface
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $filter = request('filter', 'all');
        $page = (int) request('page', 1);

        $grouped = $this->getGroupedNotifications($user->getKey(), $filter, $page);
        $unreadCount = $this->repository->unreadCountForUser($user->getKey());

        return $this->view('@notifications/index', [
            'notifications' => $grouped['notifications'],
            'total' => $grouped['total'],
            'per_page' => $grouped['per_page'],
            'current_page' => $grouped['current_page'],
            'last_page' => $grouped['last_page'],
            'filter' => $filter,
            'unread_count' => $unreadCount,
            'is_admin' => $this->isAdmin($user),
            'notice' => session('notifications.notice'),
        ]);
    }

    public function latest(): ResponseInterface
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = $this->repository->latestForUser($user->getKey(), 5);
        $unreadCount = $this->repository->unreadCountForUser($user->getKey());

        $data = array_map(
            static fn (Notification $n) => [
                'id' => $n->getKey(),
                'type' => $n->getAttribute('type'),
                'title' => $n->getAttribute('title'),
                'message' => $n->getAttribute('message'),
                'is_read' => (bool) $n->getAttribute('is_read'),
                'action_url' => $n->getAttribute('action_url'),
                'created_at' => $n->getAttribute('created_at'),
            ],
            $notifications
        );

        return $this->json([
            'notifications' => $data,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(int $id): ResponseInterface
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $result = $this->repository->markAsRead($id, $user->getKey());

        if ($result) {
            $unreadCount = $this->repository->unreadCountForUser($user->getKey());
            return $this->json(['success' => true, 'unread_count' => $unreadCount]);
        }

        return $this->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    public function markAllRead(): ResponseInterface
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $count = $this->repository->markAllAsRead($user->getKey());

        return $this->json([
            'success' => true,
            'marked_count' => $count,
            'unread_count' => 0,
        ]);
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $id = (int) ($vars['id'] ?? 0);
        $result = $this->repository->delete($id, $user->getKey());

        if ($result) {
            session()->flash('notifications.notice', 'Notification deleted successfully.');
            return $this->redirect('/admin/notifications');
        }

        session()->flash('notifications.notice', 'Notification not found.');
        return $this->redirect('/admin/notifications');
    }

    public function store(): ResponseInterface
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $type = request('type', 'info');
        $title = request('title', '');
        $message = request('message', '');
        $targetUserId = request('user_id');
        $actionUrl = request('action_url');

        if ($title === '' || $message === '') {
            return $this->json(['success' => false, 'message' => 'Title and message are required'], 422);
        }

        if (!in_array($type, [Notification::TYPE_INFO, Notification::TYPE_SUCCESS, Notification::TYPE_WARNING, Notification::TYPE_ERROR], true)) {
            $type = Notification::TYPE_INFO;
        }

        if ($targetUserId !== null) {
            $this->service->send((int) $targetUserId, $type, $title, $message, $actionUrl);
        } else {
            $this->service->sendToAdmins($type, $title, $message, $actionUrl);
        }

        return $this->json(['success' => true, 'message' => 'Notification sent']);
    }

    private function getGroupedNotifications(int $userId, string $filter, int $page): array
    {
        $notifications = $this->repository->paginatedForUser($userId, $page, 15);

        if ($filter === 'unread') {
            $notifications['data'] = array_filter(
                $notifications['data'],
                static fn (Notification $n) => !$n->getAttribute('is_read')
            );
        } elseif ($filter === 'read') {
            $notifications['data'] = array_filter(
                $notifications['data'],
                static fn (Notification $n) => (bool) $n->getAttribute('is_read')
            );
        }

        $groups = [
            'today' => [],
            'yesterday' => [],
            'last_7_days' => [],
            'last_30_days' => [],
            'older' => [],
        ];

        $today = strtotime('today');
        $yesterday = strtotime('yesterday');
        $sevenDaysAgo = strtotime('-7 days');
        $thirtyDaysAgo = strtotime('-30 days');

        foreach ($notifications['data'] as $notification) {
            $createdAt = strtotime($notification->getAttribute('created_at') ?? '');

            if ($createdAt >= $today) {
                $groups['today'][] = $notification;
            } elseif ($createdAt >= $yesterday) {
                $groups['yesterday'][] = $notification;
            } elseif ($createdAt >= $sevenDaysAgo) {
                $groups['last_7_days'][] = $notification;
            } elseif ($createdAt >= $thirtyDaysAgo) {
                $groups['last_30_days'][] = $notification;
            } else {
                $groups['older'][] = $notification;
            }
        }

        return [
            'notifications' => $groups,
            'total' => $notifications['total'],
            'per_page' => $notifications['per_page'],
            'current_page' => $notifications['current_page'],
            'last_page' => $notifications['last_page'],
        ];
    }

    private function getUser(): ?User
    {
        $user = $this->auth->user() instanceof User ? $this->auth->user() : null;
        
        if ($user === null) {
            return null;
        }

        $userId = $user->getKey();
        
        if ($userId === null || $userId === 0) {
            $actualUser = $this->getActualAdminUser();
            if ($actualUser !== null) {
                return $actualUser;
            }
        }

        return $user;
    }

    private function getActualAdminUser(): ?User
    {
        $adminRoleIds = $this->adminRoleIds();

        if ($adminRoleIds === []) {
            return null;
        }

        $builder = \App\Modules\Users\Models\User::newQuery()->getBaseBuilder()
            ->whereIn('role_id', $adminRoleIds)
            ->where('is_active', '=', 1)
            ->whereNull('deleted_at')
            ->first();

        if ($builder === null) {
            return null;
        }

        return \App\Modules\Users\Models\User::newInstance(
            is_array($builder) ? $builder : (array) $builder,
            true
        );
    }

    private function isAdmin(User $user): bool
    {
        $role = $user->role();

        return $role !== null
            && in_array((string) $role->getAttribute('slug'), ['admin', 'super_admin'], true);
    }

    /**
     * @return list<int>
     */
    private function adminRoleIds(): array
    {
        $ids = [];

        foreach (['admin', 'super_admin'] as $slug) {
            $role = Role::findBySlug($slug);

            if ($role !== null) {
                $ids[] = (int) $role->getKey();
            }
        }

        return array_values(array_unique($ids));
    }
}
