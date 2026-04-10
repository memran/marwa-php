<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;

final class UserController extends Controller
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function index(): ResponseInterface
    {
        $this->ensureViewNamespace();

        $query = trim((string) $this->request('q', ''));
        $page = max(1, (int) $this->request('page', 1));
        $builder = User::newQuery()->orderBy('created_at', 'desc');

        if ($query !== '') {
            $needle = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
            $builder->where('name', 'like', $needle)->orWhere('email', 'like', $needle);
        }

        $total = (clone $builder)->count();
        $rows = $builder
            ->offset(max(0, ($page - 1) * 10))
            ->limit(10)
            ->get();

        $pagination = [
            'data' => $rows,
            'total' => $total,
            'per_page' => 10,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / 10)),
        ];

        return $this->view('@users/index', [
            'users' => $pagination,
            'query' => $query,
            'errors' => session(ValidationException::ERROR_BAG_KEY, []),
            'notice' => session('users.notice'),
        ]);
    }

    public function create(): ResponseInterface
    {
        $this->ensureViewNamespace();

        return $this->view('@users/form', $this->formViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
        ]));
    }

    public function store(): ResponseInterface
    {
        $validated = $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role' => 'required|string|in:admin,manager,staff,viewer',
            'is_active' => 'sometimes|boolean',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'role' => trim((string) $validated['role']),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            'password' => password_hash((string) $validated['password'], PASSWORD_DEFAULT),
        ]);

        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }

    public function edit(): ResponseInterface
    {
        $this->ensureViewNamespace();

        $user = $this->findUser();

        if (!$user instanceof User) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/form', $this->formViewData([
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }

    public function update(): ResponseInterface
    {
        $user = $this->findUser();

        if (!$user instanceof User) {
            return $this->response('User not found.', 404);
        }

        $validated = $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role' => 'required|string|in:admin,manager,staff,viewer',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'role' => trim((string) $validated['role']),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : false,
        ];

        if (array_key_exists('password', $validated) && $validated['password'] !== null) {
            $payload['password'] = password_hash((string) $validated['password'], PASSWORD_DEFAULT);
        }

        $user->forceFill($payload)->saveOrFail();

        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }

    public function destroy(): ResponseInterface
    {
        $user = $this->findUser();

        if (!$user instanceof User) {
            return $this->response('User not found.', 404);
        }

        $currentUser = $this->auth->user();

        if ($currentUser instanceof User && $currentUser->getKey() === $user->getKey()) {
            $this->flash('users.notice', 'You cannot delete the active session user.');

            return $this->redirect('/admin/users');
        }

        $user->deleteOrFail();

        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function formViewData(array $extra = []): array
    {
        $user = $extra['user'] ?? null;
        $defaults = [
            'name' => $user instanceof User ? (string) $user->getAttribute('name') : '',
            'email' => $user instanceof User ? (string) $user->getAttribute('email') : '',
            'role' => $user instanceof User ? (string) $user->getAttribute('role') : 'staff',
            'is_active' => $user instanceof User ? (bool) $user->getAttribute('is_active') : true,
        ];

        $old = session(ValidationException::OLD_INPUT_KEY, []);

        if (is_array($old)) {
            foreach (['name', 'email', 'role'] as $field) {
                if (array_key_exists($field, $old)) {
                    $defaults[$field] = (string) $old[$field];
                }
            }

            if (array_key_exists('is_active', $old)) {
                $defaults['is_active'] = (bool) $old['is_active'];
            }
        }

        return array_replace([
            'errors' => session(ValidationException::ERROR_BAG_KEY, []),
            'old' => $old,
            'form' => $defaults,
            'roles' => [
                'admin' => 'Admin',
                'manager' => 'Manager',
                'staff' => 'Staff',
                'viewer' => 'Viewer',
            ],
        ], $extra);
    }

    private function findUser(): ?User
    {
        $userId = $this->request('id');

        if (!is_numeric($userId)) {
            return null;
        }

        $user = User::find((int) $userId);

        return $user instanceof User ? $user : null;
    }

    private function ensureViewNamespace(): void
    {
        if (!app()->has(\Marwa\Framework\Views\View::class)) {
            return;
        }

        app()->view()->addNamespace('users', dirname(__DIR__, 2) . '/resources/views');
    }
}
