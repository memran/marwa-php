<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Roles\Support\RoleActivityLogger;
use App\Modules\Roles\Support\RoleDataTable;
use App\Modules\Roles\Support\RoleFormData;
use App\Support\AdminListState;
use App\Support\DataTable\DataTableView;
use App\Support\Pagination;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RolesController extends Controller
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly RoleFormData $roleForms,
        private readonly RoleDataTable $roleTable,
        private readonly RoleActivityLogger $activity,
        private readonly AdminListState $listState,
        private readonly DataTableView $dataTable,
        private readonly Pagination $pagination,
    ) {}

    public function index(): ResponseInterface
    {
        $state = $this->listState->state();
        $requestParams = $this->requestParams($state, request('columns', null));

        $pageData = $this->roles->paginatedRoles(
            $state['query'],
            $state['page'],
            null,
            $state['sort'],
            $state['direction'],
            $state['filter']
        );

        $pagination = $this->pagination->viewData(
            $pageData,
            '/admin/roles',
            $this->paginationParams(
                $state,
                $this->dataTable->normalizeVisibleColumns($this->roleTable, $requestParams['columns'] ?? null)
            )
        );

        $notice = $this->consumeFlash('roles.notice');

        return $this->view('@roles/index', [
            'table' => $this->dataTable->build($this->roleTable, $requestParams, $pageData, $pagination),
            'notice' => $notice,
        ]);
    }

    public function create(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        return $this->view('@roles/form', $this->roleForms->formViewData([
            'mode' => 'create',
            'title' => 'Create Role',
            'action' => '/admin/roles',
            'submit_label' => 'Create Role',
            'back_url' => '/admin/roles',
            'role' => null,
        ]));
    }

    public function store(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $payload = $this->roleForms->payload();
        $errors = $this->roleForms->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);
            return $this->redirect('/admin/roles/create');
        }

        if ($this->roles->hasSlug($payload['slug'])) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($payload);
            return $this->redirect('/admin/roles/create');
        }

        $role = $this->roles->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'level' => $payload['level'],
            'description' => $payload['description'],
            'is_system' => 0,
        ]);
        $this->roles->syncPermissions((int) $role->getKey(), $payload['permissions']);
        $this->activity->refreshPolicy();
        $this->activity->roleCreated($role, $payload);
        session()->flash('roles.notice', 'Role created successfully.');

        return $this->redirect('/admin/roles');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roles->findById($id);
        if ($role === null) {
            session()->flash('roles.notice', 'Role not found.');
            return $this->redirect('/admin/roles');
        }

        return $this->view('@roles/form', $this->roleForms->formViewData([
            'mode' => 'edit',
            'title' => 'Edit Role',
            'action' => '/admin/roles/' . $role->getKey(),
            'submit_label' => 'Save Changes',
            'back_url' => '/admin/roles',
            'role' => $role,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roles->findById($id);
        if ($role === null) {
            session()->flash('roles.notice', 'Role not found.');
            return $this->redirect('/admin/roles');
        }

        $payload = $this->roleForms->payload();
        $errors = $this->roleForms->validate($payload, $role);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);
            return $this->redirect('/admin/roles/' . $id . '/edit');
        }

        if ($this->roles->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($payload);
            return $this->redirect('/admin/roles/' . $id . '/edit');
        }

        $this->roles->update($id, [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'level' => $payload['level'],
            'description' => $payload['description'],
        ]);
        $this->roles->syncPermissions($id, $payload['permissions']);
        $this->activity->refreshPolicy();
        $this->activity->roleUpdated($role, $payload);
        session()->flash('roles.notice', 'Role updated successfully.');

        return $this->redirect('/admin/roles');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roles->findById($id);
        if ($role === null) {
            session()->flash('roles.notice', 'Role not found.');
            return $this->redirect('/admin/roles');
        }

        if ($role->getAttribute('is_system')) {
            session()->flash('roles.notice', 'Cannot delete a system role.');
            return $this->redirect('/admin/roles');
        }

        if ($this->roles->countUsers($id) > 0) {
            session()->flash('roles.notice', 'This role is still assigned to users and cannot be deleted.');
            return $this->redirect('/admin/roles');
        }

        $state = (array) $role->toArray();
        $this->roles->delete($id);
        $this->activity->refreshPolicy();
        $this->activity->roleDeleted($id, $state);
        session()->flash('roles.notice', 'Role deleted successfully.');

        return $this->redirect('/admin/roles');
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param mixed $columns
     * @return array<string, mixed>
     */
    private function requestParams(array $state, mixed $columns): array
    {
        return [
            'q' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => $state['page'],
            'columns' => $columns,
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return array<string, scalar|list<string>|null>
     */
    private function paginationParams(array $state, array $visibleColumns): array
    {
        $params = [
            'q' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'columns' => $visibleColumns,
        ];

        return array_filter($params, static fn (mixed $value): bool => $value !== '' && $value !== []);
    }

    private function consumeFlash(string $key): ?string
    {
        $value = $this->session($key);

        if (!is_string($value) || $value === '') {
            return null;
        }

        session()->forget($key);

        return $value;
    }
}
