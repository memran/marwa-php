<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Auth\Support\RolePolicy;
use App\Modules\Roles\Support\RoleFormData;
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
            'create_url' => '/admin/roles/create',
        ]);
    }

    public function create(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        return $this->view('@roles/form', $this->roleForms()->formViewData([
            'mode' => 'create',
            'title' => 'Create Role',
            'action' => '/admin/roles',
            'submit_label' => 'Create Role',
            'role' => null,
        ]));
    }

    public function store(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $payload = $this->roleForms()->payload();
        $errors = $this->roleForms()->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($this->roleForms()->oldInput($payload));
            return $this->redirect('/admin/roles/create');
        }

        if ($this->roleRepo()->hasSlug($payload['slug'])) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($this->roleForms()->oldInput($payload));
            return $this->redirect('/admin/roles/create');
        }

        $role = $this->roleRepo()->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'level' => $payload['level'],
            'description' => $payload['description'],
            'is_system' => 0,
        ]);
        $this->syncPermissions($role->getKey(), $payload['permissions']);
        RolePolicy::loadFromDatabase();
        session()->flash('roles.notice', 'Role created successfully.');

        return $this->redirect('/admin/roles');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roleRepo()->findById($id);
        if ($role === null) {
            return $this->redirect('/admin/roles');
        }

        return $this->view('@roles/form', $this->roleForms()->formViewData([
            'mode' => 'edit',
            'title' => 'Edit Role',
            'action' => '/admin/roles/' . $role->getKey(),
            'submit_label' => 'Save Changes',
            'role' => $role,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roleRepo()->findById($id);
        if ($role === null) {
            return $this->json(['error' => 'Role not found'], 404);
        }

        $payload = $this->roleForms()->payload();
        $errors = $this->roleForms()->validate($payload, (bool) $role->getAttribute('is_system'));

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($this->roleForms()->oldInput($payload));
            return $this->redirect('/admin/roles/' . $id . '/edit');
        }

        if ($this->roleRepo()->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput($this->roleForms()->oldInput($payload));
            return $this->redirect('/admin/roles/' . $id . '/edit');
        }

        $this->roleRepo()->update($id, [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'level' => $payload['level'],
            'description' => $payload['description'],
        ]);
        $this->syncPermissions($id, $payload['permissions']);
        RolePolicy::loadFromDatabase();

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

        if ($this->roleRepo()->countUsers($id) > 0) {
            session()->flash('roles.notice', 'This role is still assigned to users and cannot be deleted.');
            return $this->redirect('/admin/roles');
        }

        $this->roleRepo()->delete($id);
        RolePolicy::loadFromDatabase();
        session()->flash('roles.notice', 'Role deleted successfully.');

        return $this->redirect('/admin/roles');
    }

    public function permissions(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $permissions = $this->permRepo()->grouped();

        return $this->view('@roles/permissions', [
            'permissions' => $permissions,
        ]);
    }

    private function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->roleRepo()->syncPermissions($roleId, $permissionIds);
    }

    private function roleForms(): RoleFormData
    {
        return app(RoleFormData::class);
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
