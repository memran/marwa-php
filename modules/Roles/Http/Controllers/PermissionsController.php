<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Roles\Support\PermissionFormData;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionsController extends Controller
{
    public function index(): ResponseInterface
    {
        return $this->view('@roles/permissions', [
            'permissions' => app(PermissionRepository::class)->grouped(),
            'create_url' => '/admin/permissions/create',
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
            $this->withErrors($errors)->withInput($this->forms()->oldInput($payload));
            return $this->redirect('/admin/permissions/create');
        }

        if ($this->permRepo()->findBySlug($payload['slug']) !== null) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($this->forms()->oldInput($payload));
            return $this->redirect('/admin/permissions/create');
        }

        $this->permRepo()->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);

        session()->flash('permissions.notice', 'Permission created successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $permission = $this->permission((int) ($vars['id'] ?? 0));
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
        $permission = $this->permission($id);
        if ($permission === null) {
            return $this->json(['error' => 'Permission not found'], 404);
        }

        $payload = $this->forms()->payload();
        $errors = $this->forms()->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($this->forms()->oldInput($payload));
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        if ($this->permRepo()->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($this->forms()->oldInput($payload));
            return $this->redirect('/admin/permissions/' . $id . '/edit');
        }

        $this->permRepo()->update($id, [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'group' => $payload['group'],
            'description' => $payload['description'],
        ]);

        session()->flash('permissions.notice', 'Permission updated successfully.');

        return $this->redirect('/admin/permissions');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $permission = $this->permission($id);
        if ($permission === null) {
            return $this->json(['error' => 'Permission not found'], 404);
        }

        $this->permRepo()->delete($id);
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

    private function permission(int $id): ?Permission
    {
        return $this->permRepo()->findById($id);
    }
}
