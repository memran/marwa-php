<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UsersTableRowActions
{
    /**
     * @return list<array<string, mixed>>
     */
    public function build(User $user, bool $isTrashed, bool $isProtectedAdmin): array
    {
        return $isTrashed
            ? $this->trashedActions($user)
            : $this->activeActions($user, $isProtectedAdmin);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trashedActions(User $user): array
    {
        return [
            $this->profileLink($user),
            $this->restoreAction($user),
            $this->disabledDeleteAction('Restored users can be edited or deleted after restore.'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeActions(User $user, bool $isProtectedAdmin): array
    {
        $actions = [$this->profileLink($user), $this->editLink($user)];

        if ($isProtectedAdmin) {
            $actions[] = $this->disabledDeleteAction('The last admin user cannot be deleted.');
            return $actions;
        }

        $actions[] = $this->deleteFormButton($user);
        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function profileLink(User $user): array
    {
        return [
            'type' => 'link',
            'label' => 'Profile',
            'href' => '/admin/users/' . $user->getKey(),
            'variant' => 'ghost',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editLink(User $user): array
    {
        return [
            'type' => 'link',
            'label' => 'Edit',
            'href' => '/admin/users/' . $user->getKey() . '/edit',
            'variant' => 'secondary',
            'permission' => 'users.edit',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deleteFormButton(User $user): array
    {
        return [
            'type' => 'form_button',
            'label' => 'Delete',
            'action' => '/admin/users/' . $user->getKey() . '/delete',
            'icon' => 'trash-2',
            'variant' => 'danger',
            'permission' => 'users.delete',
            'confirm' => 'Delete this user?',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function restoreAction(User $user): array
    {
        return [
            'type' => 'form_button',
            'label' => 'Restore',
            'action' => '/admin/users/' . $user->getKey() . '/restore',
            'icon' => 'rotate-ccw',
            'variant' => 'secondary',
            'permission' => 'users.restore',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disabledDeleteAction(string $title): array
    {
        return [
            'type' => 'button',
            'label' => 'Delete',
            'icon' => 'trash-2',
            'variant' => 'danger',
            'disabled' => true,
            'title' => $title,
        ];
    }
}
