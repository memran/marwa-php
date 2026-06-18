<?php

declare(strict_types=1);

namespace App\Modules\Roles\Http\Controllers;

use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Roles\Support\RoleActivityLogger;
use App\Modules\Roles\Support\RoleFormData;
use App\Modules\Roles\Support\RoleIndexPage;
use App\Modules\Roles\Support\RoleModuleNotice;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RolesController extends Controller
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly RoleFormData $roleForms,
        private readonly RoleIndexPage $indexPage,
        private readonly RoleActivityLogger $activity,
        private readonly RoleModuleNotice $notice,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view('@roles/index', $this->indexPage->viewData($request));
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
        $validated = $this->validate($this->roleForms->rules(), $this->roleForms->messages(), request: $request);
        $payload = $this->roleForms->normalize($validated, $this->input('permissions', []));

        if ($this->roles->hasSlug($payload['slug'])) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput();
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
        $this->notice->flash('roles.notice', 'Role created successfully.');

        return $this->redirect('/admin/roles');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roles->findById($id);
        if ($role === null) {
            $this->notice->flash('roles.notice', 'Role not found.');
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
            $this->notice->flash('roles.notice', 'Role not found.');
            return $this->redirect('/admin/roles');
        }

        $validated = $this->validate($this->roleForms->rules(), $this->roleForms->messages(), request: $request);
        $payload = $this->roleForms->normalize($validated, $this->input('permissions', []));
        $reservedSlugError = $this->roleForms->reservedSlugError($payload, $role);

        if ($reservedSlugError !== null) {
            $this->withErrors(['slug' => [$reservedSlugError]])->withInput();
            return $this->redirect('/admin/roles/' . $id . '/edit');
        }

        if ($this->roles->hasSlug($payload['slug'], $id)) {
            $this->withErrors(['slug' => ['The slug has already been taken.']])->withInput();
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
        $this->notice->flash('roles.notice', 'Role updated successfully.');

        return $this->redirect('/admin/roles');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $role = $this->roles->findById($id);
        if ($role === null) {
            $this->notice->flash('roles.notice', 'Role not found.');
            return $this->redirect('/admin/roles');
        }

        if ($role->getAttribute('is_system')) {
            $this->notice->flash('roles.notice', 'Cannot delete a system role.');
            return $this->redirect('/admin/roles');
        }

        if ($this->roles->countUsers($id) > 0) {
            $this->notice->flash('roles.notice', 'This role is still assigned to users and cannot be deleted.');
            return $this->redirect('/admin/roles');
        }

        $state = (array) $role->toArray();
        $this->roles->delete($id);
        $this->activity->refreshPolicy();
        $this->activity->roleDeleted($id, $state);
        $this->notice->flash('roles.notice', 'Role deleted successfully.');

        return $this->redirect('/admin/roles');
    }
}
