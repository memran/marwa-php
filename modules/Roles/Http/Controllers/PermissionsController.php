<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Roles\Support\PermissionActivityLogger;
use App\Modules\Roles\Support\PermissionFormData;
use App\Modules\Roles\Support\PermissionIndexPage;
use App\Modules\Roles\Support\RoleModuleNotice;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionsController extends Controller
{
    public function __construct(
        private readonly PermissionIndexPage $indexPage,
        private readonly PermissionFormData $forms,
        private readonly PermissionRepository $permissions,
        private readonly PermissionActivityLogger $activity,
        private readonly RoleModuleNotice $notice,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view('@roles/permissions', $this->indexPage->viewData($request));
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
        $validated = $this->validate($this->forms->rules(), $this->forms->messages(), request: $request);
        $payload = $this->forms->normalize($validated);

        if ($this->permissions->hasSlug($payload['slug'])) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput();
            return $this->redirect('/admin/permissions/create');
        }

        $permission = $this->permissions->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);
        $this->activity->permissionCreated($permission, $payload);
        $this->notice->flash('permissions.notice', 'Permission created successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $permission = $this->permissions->findById((int) ($vars['id'] ?? 0));
        if ($permission === null) {
            $this->notice->flash('permissions.notice', 'Permission not found.');
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
            $this->notice->flash('permissions.notice', 'Permission not found.');
            return $this->redirect('/admin/permissions');
        }

        $validated = $this->validate($this->forms->rules(), $this->forms->messages(), request: $request);
        $payload = $this->forms->normalize($validated);

        if ($this->permissions->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput();
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        $this->permissions->update($id, [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);
        $this->activity->permissionUpdated($permission, $payload);
        $this->notice->flash('permissions.notice', 'Permission updated successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $permission = $this->permissions->findById($id);
        if ($permission === null) {
            $this->notice->flash('permissions.notice', 'Permission not found.');
            return $this->redirect('/admin/permissions');
        }

        $this->permissions->delete($id);
        $this->activity->permissionDeleted($id);
        $this->notice->flash('permissions.notice', 'Permission deleted successfully.');

        return $this->redirect('/admin/permissions');
    }
}
