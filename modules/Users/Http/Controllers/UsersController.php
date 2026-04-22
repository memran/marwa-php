<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Support\AdminPagination;
use App\Support\AdminSearch;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserValidationRules;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UsersController extends Controller
{
    public function __construct(
        protected readonly UserRepository $users,
        protected readonly UserFormData $forms,
        protected readonly UserValidationRules $rules,
        protected readonly AdminSearch $search,
        protected readonly AdminPagination $pagination,
        protected readonly AuthManager $auth,
        protected readonly \App\Modules\Users\Support\UserActivityService $activity,
    ) {}

    public function index(): ResponseInterface
    {
        $search = $this->search->state();
        $users = $this->users->paginatedUsers($search['query'], $search['page']);
        $pagination = $this->pagination->viewData($users, '/admin/users', [
            'q' => $search['query'],
        ]);

        return $this->view('@users/index', [
            'users' => $users,
            'query' => $search['query'],
            'pagination' => $pagination,
            'errors' => session('errors', []),
            'notice' => session('users.notice'),
            'protected_admin_id' => $this->users->protectedAdminId(),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
        ]));
    }

    public function store(): ResponseInterface
    {
        $validated = $this->validate($this->rules->store());

        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => $this->users->normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 1,
        ];

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'])) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => $afterState['is_active'] === 1,
            ]);

            return $this->redirect('/admin/users/create');
        }

        $this->users->createUser($afterState, (string) $validated['password'], $this->auth->user());
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'user.created',
            'Created user.',
            $this->auth->user(),
            'user',
            null,
            ['state' => $afterState]
        );
        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/profile', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
        ]);
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        $validated = $this->validate($this->rules->update(), [], [], $request);
        $beforeState = $this->users->userSnapshot($user);
        $currentActive = (int) (bool) $user->getAttribute('is_active');

        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => $this->users->normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated)
                ? (int) (bool) $validated['is_active']
                : $currentActive,
        ];
        $password = array_key_exists('password', $validated) && $validated['password'] !== null
            ? (string) $validated['password']
            : null;
        $passwordChanged = $password !== null && $password !== '';

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'], (int) $user->getKey())) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => $afterState['is_active'] === 1,
            ]);

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        if ($this->users->isSelfProtectedAdmin($user, $afterState, $this->auth)) {
            $this->withErrors([
                'is_active' => ['The last admin user cannot disable themselves.'],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => false,
            ]);

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        if (!$passwordChanged && !$this->users->userStateHasChanges($beforeState, $afterState)) {
            $this->flash('users.notice', 'No changes detected.');

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        $this->users->updateUser($user, $afterState, $password, $this->auth->user());

        $payload = $afterState['is_active'] !== $beforeState['is_active']
            ? $this->activity->statusChangedPayload($user, $beforeState, $afterState)
            : $this->activity->updatedPayload($user, $beforeState, $afterState, $passwordChanged);

        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            $payload['action'],
            $payload['description'],
            $this->auth->user(),
            $payload['subjectType'],
            $payload['subjectId'],
            $payload['details']
        );
        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }

    public function restore(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        if (!empty($user->getAttribute('deleted_at'))) {
            $duplicate = $this->users->findDuplicateUserByEmail((string) $user->getAttribute('email'), (int) $user->getKey());

            if ($duplicate !== null) {
                $this->flash('users.notice', $this->users->duplicateUserMessage($duplicate));

                return $this->redirect('/admin/users');
            }
        }

        if ($this->users->restoreUser($user, $this->auth->user())) {
            app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                'user.restored',
                'Restored user account.',
                $this->auth->user(),
                'user',
                (int) $user->getKey(),
                ['state' => $this->users->userSnapshot($user)]
            );
            $this->flash('users.notice', 'User restored successfully.');
        } else {
            $this->flash('users.notice', 'Unable to restore the selected user.');
        }

        return $this->redirect('/admin/users');
    }

    public function delete(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        if ($this->users->isLastAdminUser($user)) {
            $this->flash('users.notice', 'You cannot delete the last admin user.');

            return $this->redirect('/admin/users');
        }

        if ($this->users->isActiveSessionUser($user, $this->auth)) {
            $this->flash('users.notice', 'You cannot delete the active session user.');

            return $this->redirect('/admin/users');
        }

        $this->users->deleteUser($user, $this->auth->user());
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'user.deleted',
            'Soft deleted user account.',
            $this->auth->user(),
            'user',
            (int) $user->getKey(),
            ['state' => $this->users->userSnapshot($user)]
        );
        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }
}
