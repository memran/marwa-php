<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\AdminPagination;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserValidationRules;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserListing;
use App\Modules\Users\Support\UserStatus;
use App\Modules\Users\Support\UserActivityService;
use App\Modules\Users\Support\UserBulkActions;
use App\Modules\Users\Support\UserExportActions;
use App\Modules\Users\Support\UserWriteActions;
use App\Modules\Users\Support\UsersTableConfig;
use App\Support\DataTable\DataTableRequestState;
use App\Support\DataTable\DataTableView;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UsersController extends Controller
{
    public function __construct(
        protected readonly UserRepository $users,
        protected readonly UserListing $listing,
        protected readonly UserFormData $forms,
        protected readonly UserValidationRules $rules,
        protected readonly AdminPagination $pagination,
        protected readonly AuthManager $auth,
        protected readonly UserActivityService $activity,
        protected readonly DataTableRequestState $requestState,
        protected readonly DataTableView $tableView,
        protected readonly UsersTableConfig $tableConfig,
        protected readonly UserBulkActions $bulk,
        protected readonly UserWriteActions $write,
        protected readonly UserExportActions $export,
    ) {}

    public function index(): ResponseInterface
    {
        $params = [
            'q' => request('q', ''),
            'status' => request('status', $this->tableConfig->defaultFilter()),
            'sort' => request('sort', $this->tableConfig->defaultSort()),
            'direction' => request('direction', $this->tableConfig->defaultDirection()),
            'page' => request('page', 1),
            'columns' => request('columns', []),
        ];
        $state = $this->requestState->resolve($params);
        $status = UserStatus::tryFromFilter($state['filter']);
        $visibleColumns = $this->tableView->normalizeVisibleColumns($this->tableConfig, $params['columns']);
        $users = $this->listing->paginatedUsers(
            $state['query'],
            $state['page'],
            null,
            $status,
            $state['sort'],
            $state['direction']
        );
        $pagination = $this->buildIndexPagination($state, $visibleColumns, $users);

        return $this->view('@users/index', [
            'table' => $this->tableView->build($this->tableConfig, $params, $users, $pagination),
        ]);
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function buildIndexPagination(array $state, array $visibleColumns, mixed $users): array
    {
        return $this->pagination->viewData($users, $this->tableConfig->basePath(), [
            'q' => $state['query'],
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'columns' => $visibleColumns,
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

        return $this->write->handleStore($validated);
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

        return $this->write->handleUpdate($user, $validated);
    }

    public function restore(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->write->handleRestore($user);
    }

    public function delete(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->write->handleDelete($user);
    }

    public function export(): ResponseInterface
    {
        return $this->export->exportCsv();
    }

    public function exportPdf(): ResponseInterface
    {
        return $this->export->exportPdf();
    }

    public function bulkDelete(): ResponseInterface
    {
        $params = $this->currentListParams();
        $visibleColumns = $this->tableView->normalizeVisibleColumns($this->tableConfig, $params['columns']);

        return $this->bulk->bulkDelete($params, $visibleColumns);
    }

    public function bulkStatus(): ResponseInterface
    {
        $params = $this->currentListParams();
        $visibleColumns = $this->tableView->normalizeVisibleColumns($this->tableConfig, $params['columns']);

        return $this->bulk->bulkStatus($params, $visibleColumns);
    }

    /**
     * @return array<string, mixed>
     */
    private function currentListParams(): array
    {
        return [
            'q' => request('q', ''),
            'status' => request('status', $this->tableConfig->defaultFilter()),
            'sort' => request('sort', $this->tableConfig->defaultSort()),
            'direction' => request('direction', $this->tableConfig->defaultDirection()),
            'page' => request('page', 1),
            'columns' => request('columns', []),
        ];
    }
}
