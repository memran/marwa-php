<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UsersTableColumns
{
    /**
     * @param list<string> $visibleColumns
     * @return list<array<string, mixed>>
     */
    public function buildTableColumns(array $state, array $visibleColumns, callable $buildUsersUrl): array
    {
        $columns = [];
        $sortable = ['name', 'email', 'role', 'last_login'];

        foreach ($this->columnOptions() as $key => $label) {
            if (in_array($key, $visibleColumns, true)) {
                $columns[] = $this->buildColumn($key, $label, $state, $buildUsersUrl, $sortable);
            }
        }

        return $columns;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $sortable
     * @return array<string, mixed>
     */
    private function buildColumn(
        string $key,
        string $label,
        array $state,
        callable $buildUsersUrl,
        array $sortable
    ): array {
        $isSortable = in_array($key, $sortable, true);

        return [
            'key' => $key,
            'label' => $label,
            'sortable' => $isSortable,
            'active' => $state['sort'] === $key,
            'href' => $isSortable ? $buildUsersUrl($this->sortToggleState($state, $key)) : null,
            'sort_direction' => $state['sort'] === $key ? $state['direction'] : 'desc',
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    private function sortToggleState(array $state, string $key): array
    {
        $isAsc = $state['sort'] === $key && $state['direction'] === 'asc';

        return [
            'query' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $key,
            'direction' => $isAsc ? 'desc' : 'asc',
            'page' => 1,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnOptions(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
            'last_login' => 'Last login',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildCells(User $user, bool $isProtectedAdmin): array
    {
        return [
            'name' => $this->nameCell($user),
            'email' => $this->emailCell($user),
            'role' => $this->roleCell($user),
            'status' => $this->statusCell($user, $isProtectedAdmin),
            'last_login' => $this->lastLoginCell($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nameCell(User $user): array
    {
        return [
            'type' => 'avatar_link',
            'value' => (string) $user->getAttribute('name'),
            'href' => '/admin/users/' . $user->getKey(),
            'avatar' => (string) $user->getAttribute('name'),
            'meta' => 'ID ' . (string) $user->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emailCell(User $user): array
    {
        return [
            'type' => 'text',
            'value' => (string) $user->getAttribute('email'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function roleCell(User $user): array
    {
        $role = $user->role();

        return [
            'type' => 'badge',
            'value' => $role === null ? 'Unknown' : (string) $role->getAttribute('name'),
            'tone' => 'accent',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statusCell(User $user, bool $isProtectedAdmin): array
    {
        return [
            'type' => 'badge_stack',
            'items' => $this->buildStatusBadges($user, $isProtectedAdmin),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lastLoginCell(User $user): array
    {
        return [
            'type' => 'text',
            'value' => (string) ($user->getAttribute('last_login_at') ?: 'Never'),
            'muted' => true,
        ];
    }

    /**
     * @return list<array{value:string,tone:string,icon?:string}>
     */
    public function buildStatusBadges(User $user, bool $isProtectedAdmin): array
    {
        $badges = [];

        if (!empty($user->getAttribute('deleted_at'))) {
            $badges[] = ['value' => 'Trashed', 'tone' => 'danger', 'icon' => 'trash-2'];
        } elseif ((bool) $user->getAttribute('is_active')) {
            $badges[] = ['value' => 'Active', 'tone' => 'success'];
        } else {
            $badges[] = ['value' => 'Disabled', 'tone' => 'warning'];
        }

        if ($isProtectedAdmin) {
            $badges[] = ['value' => 'Protected', 'tone' => 'warning', 'icon' => 'shield'];
        }

        return $badges;
    }
}
