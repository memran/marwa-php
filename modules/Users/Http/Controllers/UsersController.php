<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Models\Activity;
use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserDataTable;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserStatus;
use App\Support\AdminListState;
use App\Support\DataTable\DataTableView;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UsersController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserFormData $forms,
        private readonly AdminListState $listState,
        private readonly UserDataTable $userTable,
        private readonly DataTableView $dataTable,
    ) {}

    public function index(): ResponseInterface
    {
        $state = $this->listState->state();
        $columns = request('columns', null);
        $tableParams = $this->listState->tableParams(
            $state,
            $columns,
            $this->dataTable->normalizeVisibleColumns($this->userTable, $columns)
        );
        $status = UserStatus::tryFromFilter($state['filter']);

        $pageData = $this->users->paginatedUsers(
            $state['query'],
            $state['page'],
            null,
            $state['sort'],
            $state['direction'],
            $status
        );

        $pagination = pagination_view_data(
            $pageData,
            '/admin/users',
            $tableParams['pagination']
        );

        $users = User::collect();
        $activeUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 1
        );
        $disabledUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 0
        );
        $trashedUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) !== ''
        );

        $stats = [
            'total' => $activeUsers->count() + $disabledUsers->count(),
            'active' => $activeUsers->count(),
            'disabled' => $disabledUsers->count(),
            'trashed' => $trashedUsers->count(),
        ];

        return $this->view('@users/index', [
            'stats' => $stats,
            'table' => $this->dataTable->build($this->userTable, $tableParams['request'], $pageData, $pagination),
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

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activityPageData = $this->activityPageData(
            (string) $user->getAttribute('email'),
            $activityPage
        );

        return $this->view('@users/show', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activityPageData['data'],
            'activity_total' => $activityPageData['pagination']['total'],
            'activity_pagination' => pagination_view_data(
                $activityPageData['pagination'],
                '/admin/users/' . $user->getKey(),
                ['tab' => 'activity'],
                'activity_page'
            ),
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

    /**
     * @return array{data:list<Activity>,pagination:array{total:int,per_page:int,current_page:int,last_page:int}}
     */
    private function activityPageData(string $email, int $page, int $perPage = 5): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        try {
            $activity = new Activity();
            $query = Activity::query();
            $builder = $query->getBaseBuilder();

            $activity->scopeActorEmail($builder, $email);
            $activity->scopeSort($builder, 'created_at', 'desc');

            $pageData = $query->paginate($perPage, $page);
        } catch (\Throwable) {
            $pageData = [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1,
            ];
        }

        return [
            'data' => $pageData['data'],
            'pagination' => [
                'total' => (int) $pageData['total'],
                'per_page' => (int) $pageData['per_page'],
                'current_page' => (int) $pageData['current_page'],
                'last_page' => (int) $pageData['last_page'],
            ],
        ];
    }
}
