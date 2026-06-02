<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Http\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Marwa\Router\Response;

final class UserWriteActions
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserActivityService $activity,
        private readonly AuthManager $auth,
    ) {}

    /**
     * @return ResponseInterface
     */
    public function handleStore(array $validated): ResponseInterface
    {
        $afterState = $this->stateFromValidated($validated);

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'])) {
            return $this->redirectWithErrors(
                '/admin/users/create',
                ['email' => [$this->users->duplicateUserMessage($duplicate)]],
                $this->inputFromState($afterState)
            );
        }

        $user = $this->users->createUser($afterState, (string) $validated['password']);
        $this->activity->recordCreated($user, $afterState, $this->auth->user());
        session()->flash('users.notice', 'User created successfully.');

        return Response::redirect('/admin/users');
    }

    public function handleRestore(User $user): ResponseInterface
    {
        if ($this->isTrashedDuplicate($user)) {
            return $this->flashAndRedirect($this->users->duplicateUserMessage($this->duplicateOf($user)));
        }

        if ($this->users->restoreUser($user)) {
            $this->activity->recordRestored($user, $this->auth->user());
            return $this->flashAndRedirect('User restored successfully.');
        }

        return $this->flashAndRedirect('Unable to restore the selected user.');
    }

    private function isTrashedDuplicate(User $user): bool
    {
        if (empty($user->getAttribute('deleted_at'))) {
            return false;
        }

        return $this->duplicateOf($user) !== null;
    }

    private function duplicateOf(User $user): ?User
    {
        return $this->users->findDuplicateUserByEmail(
            (string) $user->getAttribute('email'),
            (int) $user->getKey()
        );
    }

    private function flashAndRedirect(string $message): ResponseInterface
    {
        session()->flash('users.notice', $message);

        return Response::redirect('/admin/users');
    }

    public function handleDelete(User $user): ResponseInterface
    {
        if ($this->users->isLastAdminUser($user)) {
            session()->flash('users.notice', 'You cannot delete the last admin user.');
            return Response::redirect('/admin/users');
        }

        if ($this->users->isActiveSessionUser($user, $this->auth)) {
            session()->flash('users.notice', 'You cannot delete the active session user.');
            return Response::redirect('/admin/users');
        }

        $this->users->deleteUser($user);
        $this->activity->recordDeleted($user, $this->auth->user());
        session()->flash('users.notice', 'User deleted successfully.');

        return Response::redirect('/admin/users');
    }

    /**
     * @return ResponseInterface
     */
    public function handleUpdate(User $user, array $validated): ResponseInterface
    {
        $beforeState = $this->users->userSnapshot($user);
        $afterState = $this->buildUpdateState($user, $validated);
        $password = $this->extractPassword($validated);
        $passwordChanged = $password !== null && $password !== '';
        $editPath = $this->editPath($user);

        $guardError = $this->checkUpdateGuards($user, $afterState, $editPath);
        if ($guardError !== null) {
            return $guardError;
        }

        if (!$passwordChanged && !$this->users->userStateHasChanges($beforeState, $afterState)) {
            session()->flash('users.notice', 'No changes detected.');
            return Response::redirect($editPath);
        }

        return $this->applyUpdate($user, $beforeState, $afterState, $password, $passwordChanged);
    }

    private function editPath(User $user): string
    {
        return '/admin/users/' . $user->getKey() . '/edit';
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    private function checkUpdateGuards(User $user, array $afterState, string $editPath): ?ResponseInterface
    {
        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'], (int) $user->getKey())) {
            return $this->redirectWithErrors(
                $editPath,
                ['email' => [$this->users->duplicateUserMessage($duplicate)]],
                $this->inputFromState($afterState)
            );
        }

        if ($this->users->isSelfProtectedAdmin($user, $afterState, $this->auth)) {
            return $this->redirectWithErrors(
                $editPath,
                ['is_active' => ['The last admin user cannot disable themselves.']],
                array_merge($this->inputFromState($afterState), ['is_active' => false])
            );
        }

        return null;
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $beforeState
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    private function applyUpdate(
        User $user,
        array $beforeState,
        array $afterState,
        ?string $password,
        bool $passwordChanged
    ): ResponseInterface {
        $this->users->updateUser($user, $afterState, $password);
        $this->recordUpdateActivity($user, $beforeState, $afterState, $passwordChanged);

        session()->flash('users.notice', 'User updated successfully.');
        return Response::redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{name:string,email:string,role_id:int,is_active:int}
     */
    private function buildUpdateState(User $user, array $validated): array
    {
        $currentActive = (int) (bool) $user->getAttribute('is_active');

        return [
            'name' => trim((string) $validated['name']),
            'email' => User::normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated)
                ? (int) (bool) $validated['is_active']
                : $currentActive,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function extractPassword(array $validated): ?string
    {
        if (!array_key_exists('password', $validated) || $validated['password'] === null) {
            return null;
        }

        return (string) $validated['password'];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $beforeState
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    private function recordUpdateActivity(
        User $user,
        array $beforeState,
        array $afterState,
        bool $passwordChanged
    ): void {
        $actor = $this->auth->user();

        if ($afterState['is_active'] !== $beforeState['is_active']) {
            $this->activity->recordStatusChanged($user, $beforeState, $afterState, $actor);
            return;
        }

        $this->activity->recordUpdated($user, $beforeState, $afterState, $passwordChanged, $actor);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{name:string,email:string,role_id:int,is_active:int}
     */
    private function stateFromValidated(array $validated): array
    {
        return [
            'name' => trim((string) $validated['name']),
            'email' => User::normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 1,
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $state
     * @return array{name:string,email:string,role_id:int,is_active:bool}
     */
    private function inputFromState(array $state): array
    {
        return [
            'name' => $state['name'],
            'email' => $state['email'],
            'role_id' => $state['role_id'],
            'is_active' => $state['is_active'] === 1,
        ];
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed> $input
     */
    private function redirectWithErrors(string $path, array $errors, array $input): ResponseInterface
    {
        session()->flash('errors', $errors);
        session()->flash('_old_input', $input);

        return Response::redirect($path);
    }
}
