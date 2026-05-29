<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Roles\Support\PermissionFormData;
use App\Support\AdminListState;
use App\Support\AdminPagination;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionsController extends Controller
{
    public function __construct(
        private readonly AdminListState $listState,
        private readonly AdminPagination $pagination,
    ) {}

    public function index(): ResponseInterface
    {
        $request = $this->request();
        $params = $request->getQueryParams();
        $repository = app(PermissionRepository::class);
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

        $permissions = $repository->paginatedGroupedFiltered(
            $state['query'],
            $group,
            $state['page'],
            null,
            $sort,
            $direction
        );
        $visiblePermissions = (int) $permissions['total'];
        $groupCount = count($permissions['groups']);

        return $this->view('@roles/permissions', [
            'permissions' => $permissions['groups'],
            'group_options' => $repository->groupNames(),
            'query' => $state['query'],
            'group' => $group,
            'sort' => $sort,
            'direction' => $direction,
            'visible_permissions' => $visiblePermissions,
            'group_count' => $groupCount,
            'total_permissions' => count($repository->all()),
            'create_url' => '/admin/permissions/create',
            'pagination' => $this->pagination->viewData($permissions, '/admin/permissions', [
                'q' => $state['query'],
                'group' => $group,
                'sort' => $sort,
                'direction' => $direction,
            ]),
        ]);
    }

    public function create(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        return $this->view('@roles/permissions-form', $this->forms()->formViewData([
            'mode' => 'create',
            'title' => 'Create Permission',
            'action' => '/admin/permissions',
            'submit_label' => 'Create Permission',
            'permission' => null,
        ]));
    }

    public function store(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $payload = $this->forms()->payload();
        $errors = $this->forms()->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);
            return $this->redirect('/admin/permissions/create');
        }

        if ($this->permRepo()->findBySlug($payload['slug']) !== null) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($payload);
            return $this->redirect('/admin/permissions/create');
        }

        $this->permRepo()->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'permission.created',
            'Created permission.',
            app(\App\Modules\Auth\Support\AuthManager::class)->user(),
            'permission',
            null,
            ['state' => $payload]
        );
        session()->flash('permissions.notice', 'Permission created successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $permission = $this->permRepo()->findById((int) ($vars['id'] ?? 0));
        if ($permission === null) {
            return $this->redirect('/admin/permissions');
        }

        return $this->view('@roles/permissions-form', $this->forms()->formViewData([
            'mode' => 'edit',
            'title' => 'Edit Permission',
            'action' => '/admin/permissions/' . $permission->getKey(),
            'submit_label' => 'Save Changes',
            'permission' => $permission,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $permission = $this->permRepo()->findById($id);
        if ($permission === null) {
            return $this->json(['error' => 'Permission not found'], 404);
        }

        $payload = $this->forms()->payload();
        $errors = $this->forms()->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        if ($this->permRepo()->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($payload);
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        $this->permRepo()->update($id, [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'permission.updated',
            'Updated permission.',
            app(\App\Modules\Auth\Support\AuthManager::class)->user(),
            'permission',
            $id,
            ['state' => $payload]
        );
        session()->flash('permissions.notice', 'Permission updated successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $permission = $this->permRepo()->findById($id);
        if ($permission === null) {
            return $this->json(['error' => 'Permission not found'], 404);
        }

        $this->permRepo()->delete($id);
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'permission.deleted',
            'Deleted permission.',
            app(\App\Modules\Auth\Support\AuthManager::class)->user(),
            'permission',
            $id,
            ['state' => ['id' => $id]]
        );
        session()->flash('permissions.notice', 'Permission deleted successfully.');

        return $this->redirect('/admin/permissions');
    }

    private function forms(): PermissionFormData
    {
        return app(PermissionFormData::class);
    }

    private function permRepo(): PermissionRepository
    {
        return app(PermissionRepository::class);
    }

}
