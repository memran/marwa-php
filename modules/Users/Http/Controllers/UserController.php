<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Framework\Validation\ValidationException;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ServerRequestInterface;
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
        $rows = array_map(
            static fn (array|object $row): User => User::newInstance(is_array($row) ? $row : (array) $row, true),
            User::newQuery()->getBaseBuilder()
                ->orderBy('created_at', 'desc')
                ->get()
        );

        if ($query !== '') {
            $rows = array_values(array_filter($rows, static function (User $user) use ($query): bool {
                $name = strtolower(trim((string) $user->getAttribute('name')));
                $email = strtolower(trim((string) $user->getAttribute('email')));
                $needle = strtolower($query);

                return str_contains($name, $needle) || str_contains($email, $needle);
            }));
        }

        $visibleAdmins = array_values(array_filter($rows, static function (User $user): bool {
            return strtolower(trim((string) $user->getAttribute('role'))) === 'admin'
                && empty($user->getAttribute('deleted_at'));
        }));
        $protectedAdminId = count($visibleAdmins) === 1 ? $visibleAdmins[0]->getKey() : null;

        $total = count($rows);
        $rows = array_slice($rows, max(0, ($page - 1) * 10), 10);

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
            'protected_admin_id' => $protectedAdminId,
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
        $validated = app(RequestValidator::class)->validateInput(Input::all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role' => 'required|string|in:admin,manager,staff,viewer',
            'is_active' => 'sometimes|boolean',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = trim((string) $validated['email']);
        $isActive = array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 1;

        if ($duplicate = $this->findDuplicateUserByEmail($email)) {
            $this->withErrors([
                'email' => [$this->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => trim((string) $validated['name']),
                'email' => $email,
                'role' => trim((string) $validated['role']),
                'is_active' => $isActive === 1,
            ]);

            return $this->redirect('/admin/users/create');
        }

        User::create([
            'name' => trim((string) $validated['name']),
            'email' => $email,
            'role' => trim((string) $validated['role']),
            'is_active' => $isActive,
            'password' => password_hash((string) $validated['password'], PASSWORD_DEFAULT),
        ]);

        $created = User::findBy('email', $email);

        if ($created instanceof User) {
            (new ActivityRecorder())->recordUserAction(
                'user.created',
                'Created user ' . $email . '.',
                $this->auth->user(),
                $created,
                [
                    'summary' => 'Created user account.',
                    'changes' => [
                        'name' => ['before' => null, 'after' => trim((string) $validated['name'])],
                        'email' => ['before' => null, 'after' => $email],
                        'role' => ['before' => null, 'after' => trim((string) $validated['role'])],
                        'status' => ['before' => null, 'after' => $isActive === 1 ? 'active' : 'disabled'],
                    ],
                ]
            );
        }

        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $this->ensureViewNamespace();

        $user = $this->findUser($vars);

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

    /**
     * @param array<string, mixed> $vars
     */
    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->findUser($vars);

        if (!$user instanceof User) {
            return $this->response('User not found.', 404);
        }

        $validated = app(RequestValidator::class)->validateInput(Input::all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role' => 'required|string|in:admin,manager,staff,viewer',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $email = trim((string) $validated['email']);
        $isActive = array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 0;
        $beforeState = $this->userSnapshot($user);
        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => $email,
            'role' => trim((string) $validated['role']),
            'is_active' => $isActive,
        ];
        $passwordChanged = array_key_exists('password', $validated) && $validated['password'] !== null;

        if ($duplicate = $this->findDuplicateUserByEmail($email, (int) $user->getKey())) {
            $this->withErrors([
                'email' => [$this->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => trim((string) $validated['name']),
                'email' => $email,
                'role' => trim((string) $validated['role']),
                'is_active' => $isActive === 1,
            ]);

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        $currentUser = $this->auth->user();
        $currentEmail = $currentUser instanceof User
            ? strtolower(trim((string) $currentUser->getAttribute('email')))
            : '';
        $targetEmail = strtolower(trim((string) $user->getAttribute('email')));

        if (
            $currentEmail !== ''
            && $currentEmail === $targetEmail
            && $this->isLastAdminUser($user)
            && $isActive === 0
        ) {
            $this->withErrors([
                'is_active' => ['The last admin user cannot disable themselves.'],
            ])->withInput([
                'name' => trim((string) $validated['name']),
                'email' => $email,
                'role' => trim((string) $validated['role']),
                'is_active' => false,
            ]);

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        if (!$passwordChanged && !$this->userStateHasChanges($beforeState, $afterState)) {
            $this->flash('users.notice', 'No changes detected.');

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        $payload = $afterState;

        if ($passwordChanged) {
            $payload['password'] = password_hash((string) $validated['password'], PASSWORD_DEFAULT);
        }

        $user->forceFill($payload)->saveOrFail();

        (new ActivityRecorder())->recordUserAction(
            'user.updated',
            'Updated user ' . $email . '.',
            $this->auth->user(),
            $user,
            [
                'summary' => $this->userUpdateSummary($beforeState, $afterState, $passwordChanged),
                'changes' => $this->userChanges($beforeState, $afterState, $passwordChanged),
                'before' => $this->userReadableState($beforeState),
                'after' => $this->userReadableState($afterState),
            ]
        );

        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->findUser($vars);

        if (!$user instanceof User) {
            return $this->response('User not found.', 404);
        }

        if ($this->isLastAdminUser($user)) {
            $this->flash('users.notice', 'You cannot delete the last admin user.');

            return $this->redirect('/admin/users');
        }

        $currentUser = $this->auth->user();

        if ($currentUser instanceof User) {
            $currentEmail = strtolower(trim((string) $currentUser->getAttribute('email')));
            $targetEmail = strtolower(trim((string) $user->getAttribute('email')));

            if ($currentEmail !== '' && $currentEmail === $targetEmail) {
                $this->flash('users.notice', 'You cannot delete the active session user.');

                return $this->redirect('/admin/users');
            }
        }

        if ($currentUser instanceof User && $currentUser->getKey() === $user->getKey()) {
            $this->flash('users.notice', 'You cannot delete the active session user.');

            return $this->redirect('/admin/users');
        }

        (new ActivityRecorder())->recordUserAction(
            'user.deleted',
            'Deleted user ' . (string) $user->getAttribute('email') . '.',
            $this->auth->user(),
            $user,
            [
                'summary' => 'Soft deleted user account.',
                'state' => $this->userReadableState($this->userSnapshot($user)),
            ]
        );

        $user->deleteOrFail();

        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function restore(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->findUser($vars, true);

        if (!$user instanceof User) {
            return $this->response('User not found.', 404);
        }

        if (!empty($user->getAttribute('deleted_at')) && ($duplicate = $this->findDuplicateUserByEmail((string) $user->getAttribute('email'), (int) $user->getKey()))) {
            $this->flash('users.notice', $this->duplicateUserMessage($duplicate));

            return $this->redirect('/admin/users');
        }

        if ($user->restore()) {
            (new ActivityRecorder())->recordUserAction(
                'user.restored',
                'Restored user ' . (string) $user->getAttribute('email') . '.',
                $this->auth->user(),
                $user,
                [
                    'summary' => 'Restored user account.',
                    'state' => $this->userReadableState($this->userSnapshot($user)),
                ]
            );
            $this->flash('users.notice', 'User restored successfully.');
        } else {
            $this->flash('users.notice', 'Unable to restore the selected user.');
        }

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

    /**
     * @param array<string, mixed> $vars
     */
    private function findUser(array $vars = [], bool $includeTrashed = false): ?User
    {
        $userId = $vars['id'] ?? null;

        if (!is_numeric($userId)) {
            return null;
        }

        $user = $includeTrashed
            ? User::withTrashed()->find((int) $userId)
            : User::find((int) $userId);

        return $user instanceof User ? $user : null;
    }

    private function isLastAdminUser(User $user): bool
    {
        if (strtolower(trim((string) $user->getAttribute('role'))) !== 'admin') {
            return false;
        }

        return User::newQuery()->getBaseBuilder()
            ->whereNull('deleted_at')
            ->where('role', '=', 'admin')
            ->count() <= 1;
    }

    private function findDuplicateUserByEmail(string $email, ?int $ignoreId = null): ?User
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        $users = User::newQuery()->getBaseBuilder()
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($users as $row) {
            $candidate = User::newInstance(is_array($row) ? $row : (array) $row, true);

            if ($ignoreId !== null && $candidate->getKey() === $ignoreId) {
                continue;
            }

            if (strtolower(trim((string) $candidate->getAttribute('email'))) === $email) {
                return $candidate;
            }
        }

        return null;
    }

    private function duplicateUserMessage(User $duplicate): string
    {
        if (!empty($duplicate->getAttribute('deleted_at'))) {
            return 'Duplicate user: a trashed user already uses this email. Restore that user or choose another email.';
        }

        return 'Duplicate user: this email already belongs to another user.';
    }

    /**
     * @return array{name: string, email: string, role: string, is_active: int}
     */
    private function userSnapshot(User $user): array
    {
        return [
            'name' => trim((string) $user->getAttribute('name')),
            'email' => trim((string) $user->getAttribute('email')),
            'role' => trim((string) $user->getAttribute('role')),
            'is_active' => (int) (bool) $user->getAttribute('is_active'),
        ];
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $before
     * @param array{name: string, email: string, role: string, is_active: int} $after
     */
    private function userStateHasChanges(array $before, array $after): bool
    {
        return $before !== $after;
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $state
     */
    /**
     * @param array{name: string, email: string, role: string, is_active: int} $state
     */
    private function userReadableState(array $state): array
    {
        return [
            'Name' => $state['name'] !== '' ? $state['name'] : 'Unknown',
            'Email' => $state['email'] !== '' ? $state['email'] : 'Unknown',
            'Role' => $state['role'] !== '' ? $state['role'] : 'Unknown',
            'Status' => $state['is_active'] === 1 ? 'Active' : 'Disabled',
        ];
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $before
     * @param array{name: string, email: string, role: string, is_active: int} $after
     */
    private function userChanges(array $before, array $after, bool $passwordChanged): array
    {
        $changes = [];

        foreach (['name', 'email', 'role', 'is_active'] as $field) {
            if ($before[$field] !== $after[$field]) {
                $label = match ($field) {
                    'is_active' => 'Status',
                    default => ucfirst($field),
                };

                $changes[$label] = [
                    'before' => $field === 'is_active'
                        ? ($before[$field] === 1 ? 'Active' : 'Disabled')
                        : $before[$field],
                    'after' => $field === 'is_active'
                        ? ($after[$field] === 1 ? 'Active' : 'Disabled')
                        : $after[$field],
                ];
            }
        }

        if ($passwordChanged) {
            $changes['Password'] = [
                'before' => 'Unchanged',
                'after' => 'Updated',
            ];
        }

        return $changes;
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $before
     * @param array{name: string, email: string, role: string, is_active: int} $after
     */
    private function userUpdateSummary(array $before, array $after, bool $passwordChanged): string
    {
        $labels = array_keys($this->userChanges($before, $after, $passwordChanged));

        if ($labels === []) {
            return 'No fields changed.';
        }

        return 'Changed fields: ' . implode(', ', $labels) . '.';
    }

    private function ensureViewNamespace(): void
    {
        if (!app()->has(\Marwa\Framework\Views\View::class)) {
            return;
        }

        app()->view()->addNamespace('users', dirname(__DIR__, 2) . '/resources/views');
    }
}
