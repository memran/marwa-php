<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserStatus;
use App\Support\AdminListState;
use App\Support\Pagination;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UsersController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly UserRepository $users,
        private readonly UserFormData $forms,
        private readonly AdminListState $listState,
        private readonly Pagination $pagination,
    ) {
    }

    public function profile(): ResponseInterface
    {
        return $this->view('@users/profile', [
            'authUser' => $this->auth->user(),
        ]);
    }

    public function index(): ResponseInterface
    {
        $state = $this->listState->state();
        $status = UserStatus::tryFromFilter($state['filter']);

        $pageData = $this->users->paginatedUsers(
            $state['query'],
            $state['page'],
            null,
            $state['sort'],
            $state['direction'],
            $status
        );

        $pagination = $this->pagination->viewData(
            $pageData,
            '/admin/users',
            $this->listState->paginationParams($state)
        );

        return $this->view('@users/index', [
            ...$state,
            'users' => $pageData['data'],
            'protected_admin_id' => $this->users->protectedAdminId(),
            'pagination' => $pagination,
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->view('@users/form', $this->sharedFormViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
            'user' => null,
        ]));
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $this->forms->payload($request);
        $errors = $this->forms->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);

            return $this->redirect('/admin/users/create');
        }

        $this->users->createUser($payload);
        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        return $this->view('@users/show', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
        ]);
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        return $this->view('@users/form', $this->sharedFormViewData([
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $user = $this->users->findById($id);

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        $payload = $this->forms->payload($request);
        $errors = $this->forms->validate($payload, $user);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);

            return $this->redirect('/admin/users/' . $id . '/edit');
        }

        $this->users->updateUser($user, $payload, $payload['password'] !== '' ? $payload['password'] : null);
        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        if ($this->users->isLastAdminUser($user)) {
            $this->flash('users.notice', 'The last admin user cannot be deleted.');

            return $this->redirect('/admin/users');
        }

        $this->users->deleteUser($user);
        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function sharedFormViewData(array $extra): array
    {
        $oldInput = $this->session('_old_input', []);
        $errors = $this->session('errors', []);

        return $this->forms->formViewData(
            $extra,
            is_array($oldInput) ? $oldInput : [],
            is_array($errors) ? $errors : []
        );
    }
}
