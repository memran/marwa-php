<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Auth\Support\PermissionRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RolesController extends Controller
{
    public function index(): ResponseInterface
    {
        $roles = $this->roleRepo()->all();

        return $this->view('@roles/index', [
            'roles' => $roles,
        ]);
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roleRepo()->findById($id);
        if ($role === null) {
            return $this->redirect('/admin/roles');
        }

        $allPermissions = $this->permRepo()->grouped();
        $rolePermissions = $this->roleRepo()->getPermissions($id);
        $rolePermissionIds = array_map(
            static fn ($p) => $p->getKey(),
            $rolePermissions
        );

        return $this->view('@roles/edit', [
            'role' => $role,
            'permissions' => $allPermissions,
            'role_permission_ids' => $rolePermissionIds,
        ]);
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roleRepo()->findById($id);
        if ($role === null) {
            return $this->json(['error' => 'Role not found'], 404);
        }

        $name = request('name', '');
        $description = request('description', '');
        $permissionIds = request('permissions', []);

        if ($name === '') {
            return $this->json(['error' => 'Name is required'], 422);
        }

        $this->roleRepo()->update($id, [
            'name' => $name,
            'description' => $description,
        ]);

        if (is_array($permissionIds)) {
            $this->roleRepo()->syncPermissions($id, array_map('intval', $permissionIds));
        }

        session()->flash('roles.notice', 'Role updated successfully.');

        return $this->redirect('/admin/roles');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roleRepo()->findById($id);
        if ($role === null) {
            return $this->json(['error' => 'Role not found'], 404);
        }

        if ($role->getAttribute('is_system')) {
            return $this->json(['error' => 'Cannot delete system role'], 422);
        }

        $this->roleRepo()->delete($id);

        return $this->json(['success' => true]);
    }

    public function permissions(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $permissions = $this->permRepo()->grouped();

        return $this->view('@roles/permissions', [
            'permissions' => $permissions,
        ]);
    }

    private function roleRepo(): RoleRepository
    {
        return app(RoleRepository::class);
    }

    private function permRepo(): PermissionRepository
    {
        return app(PermissionRepository::class);
    }
}
