<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Roles\Support\PermissionActivityLogger;
use App\Modules\Roles\Support\PermissionFormData;
use App\Support\AdminListState;
use App\Support\Pagination;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionsController extends Controller
{
    public function __construct(
        private readonly AdminListState $listState,
        private readonly Pagination $pagination,
        private readonly PermissionFormData $forms,
        private readonly PermissionRepository $permissions,
        private readonly PermissionActivityLogger $activity,
    ) {}

    public function index(): ResponseInterface
    {
        $request = $this->request();
        $params = $request->getQueryParams();
        $state = $this->listState->stateFrom(
            $params,
            'q',
            'group',
            'sort',
            'direction',
            'page'
        );
        $group = isset($params['group']) ? trim((string) $params['group']) : '';
        $sort = isset($params['sort']) ? trim((string) $params['sort']) : 'group';
        $direction = isset($params['direction']) ? strtolower(trim((string) $params['direction'])) : 'asc';

        if ($group === 'all') {
            $group = '';
        }

        if ($sort === '' || $sort === 'created_at') {
            $sort = 'group';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $pageData = $this->permissions->paginatedGroupedFiltered(
            $state['query'],
            $group,
            $state['page'],
            null,
            $sort,
            $direction
        );
        $visiblePermissions = (int) $pageData['total'];
        $groupCount = count($pageData['groups']);

        $notice = $this->consumeFlash('permissions.notice');

        return $this->view('@roles/permissions', [
            'permissions' => $pageData['groups'],
            'group_options' => $this->permissions->groupNames(),
            'query' => $state['query'],
            'group' => $group,
            'sort' => $sort,
            'direction' => $direction,
            'visible_permissions' => $visiblePermissions,
            'group_count' => $groupCount,
            'total_permissions' => count($this->permissions->all()),
            'create_url' => '/admin/permissions/create',
            'pagination' => $this->pagination->viewData($pageData, '/admin/permissions', [
                'q' => $state['query'],
                'group' => $group,
                'sort' => $sort,
                'direction' => $direction,
            ]),
            'notice' => $notice,
        ]);
    }

    public function create(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        return $this->view('@roles/permissions-form', $this->forms->formViewData([
            'mode' => 'create',
            'title' => 'Create Permission',
            'action' => '/admin/permissions',
            'submit_label' => 'Create Permission',
            'back_url' => '/admin/permissions',
            'permission' => null,
        ]));
    }

    public function store(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $payload = $this->forms->payload();
        $errors = $this->forms->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);
            return $this->redirect('/admin/permissions/create');
        }

        if ($this->permissions->findBySlug($payload['slug']) !== null) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($payload);
            return $this->redirect('/admin/permissions/create');
        }

        $permission = $this->permissions->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);
        $this->activity->permissionCreated($permission, $payload);
        session()->flash('permissions.notice', 'Permission created successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $permission = $this->permissions->findById((int) ($vars['id'] ?? 0));
        if ($permission === null) {
            session()->flash('permissions.notice', 'Permission not found.');
            return $this->redirect('/admin/permissions');
        }

        return $this->view('@roles/permissions-form', $this->forms->formViewData([
            'mode' => 'edit',
            'title' => 'Edit Permission',
            'action' => '/admin/permissions/' . $permission->getKey(),
            'submit_label' => 'Save Changes',
            'back_url' => '/admin/permissions',
            'permission' => $permission,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $permission = $this->permissions->findById($id);
        if ($permission === null) {
            session()->flash('permissions.notice', 'Permission not found.');
            return $this->redirect('/admin/permissions');
        }

        $payload = $this->forms->payload();
        $errors = $this->forms->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        if ($this->permissions->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($payload);
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        $this->permissions->update($id, [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);
        $this->activity->permissionUpdated($permission, $payload);
        session()->flash('permissions.notice', 'Permission updated successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $permission = $this->permissions->findById($id);
        if ($permission === null) {
            session()->flash('permissions.notice', 'Permission not found.');
            return $this->redirect('/admin/permissions');
        }

        $this->permissions->delete($id);
        $this->activity->permissionDeleted($id);
        session()->flash('permissions.notice', 'Permission deleted successfully.');

        return $this->redirect('/admin/permissions');
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
