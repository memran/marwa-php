<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UsersTableToolbar
{
    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    public function buildSearch(array $state, array $visibleColumns, callable $buildUsersUrl, callable $hiddenFields): array
    {
        return [
            'action' => '/admin/users',
            'value' => $state['query'],
            'placeholder' => 'Search anything...',
            'aria_label' => 'Search users',
            'submit_label' => 'Search users',
            'clear_label' => 'Clear search',
            'clear_url' => $buildUsersUrl($this->clearedQueryState($state)),
            'hidden_fields' => $hiddenFields($this->searchFormHiddenParams($state), $visibleColumns),
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    private function clearedQueryState(array $state): array
    {
        return [
            'query' => '',
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => 1,
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{status:string,sort:string,direction:string}
     */
    private function searchFormHiddenParams(array $state): array
    {
        return [
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    public function buildFilter(array $state, array $visibleColumns, callable $buildUsersUrl): array
    {
        return [
            'label' => 'Filters',
            'current_label' => ucfirst(str_replace('_', ' ', $state['filter'])),
            'items' => $this->buildFilterItems($state, $visibleColumns, $buildUsersUrl),
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param array<string, string> $columnOptions
     */
    public function buildColumnsToolbar(
        array $state,
        array $visibleColumns,
        array $columnOptions,
        callable $buildUsersUrl,
        callable $hiddenFields
    ): array {
        return [
            'label' => 'Columns',
            'legend' => 'Visible columns',
            'visible_count' => count($visibleColumns),
            'action' => '/admin/users',
            'reset_url' => $buildUsersUrl($this->resetState($state), array_keys($columnOptions)),
            'hidden_fields' => $hiddenFields($this->searchHiddenParams($state)),
            'items' => $this->buildColumnItems($visibleColumns, $columnOptions),
            'submit_label' => 'Apply',
            'reset_label' => 'Reset',
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param list<array<string, mixed>> $rows
     */
    public function buildBulk(array $state, array $rows, array $visibleColumns, callable $hiddenFields): array
    {
        return [
            'form_id' => 'users-bulk-form',
            'action_delete_url' => '/admin/users/bulk-delete',
            'action_status_url' => '/admin/users/bulk-status',
            'selectable_count' => $this->countSelectable($rows),
            'select_all_label' => 'Select all',
            'selection_header_label' => 'Select rows',
            'status_placeholder' => 'Bulk status',
            'status_label' => 'Update status',
            'status_icon' => 'refresh-cw',
            'status_options' => $this->statusOptions(),
            'delete_label' => 'Delete selected',
            'delete_icon' => 'trash-2',
            'delete_confirm' => 'Delete the selected users?',
            'hidden_fields' => $hiddenFields($this->bulkHiddenParams($state), $visibleColumns),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function countSelectable(array $rows): int
    {
        return count(array_filter(
            $rows,
            static fn (array $row): bool => !($row['bulk']['disabled'] ?? false)
        ));
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => UserStatus::Active->value, 'label' => 'Mark active'],
            ['value' => UserStatus::Disabled->value, 'label' => 'Mark disabled'],
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array<string, string|int>
     */
    private function bulkHiddenParams(array $state): array
    {
        return [
            'q' => $state['query'],
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => $state['page'],
        ];
    }

    /**
     * @param array<string, string|int|list<string>|null> $params
     * @param list<string> $visibleColumns
     */
    public function hiddenFields(array $params, array $visibleColumns = []): array
    {
        $fields = $this->hiddenParamFields($params);

        foreach ($visibleColumns as $column) {
            $fields[] = ['name' => 'columns[]', 'value' => $column];
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{name:string,value:string}>
     */
    private function hiddenParamFields(array $params): array
    {
        $fields = [];

        foreach ($params as $name => $value) {
            if ($value !== null && $value !== '') {
                $fields[] = ['name' => (string) $name, 'value' => (string) $value];
            }
        }

        return $fields;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    private function buildFilterItems(array $state, array $visibleColumns, callable $buildUsersUrl): array
    {
        $items = [];

        foreach (UserStatus::cases() as $status) {
            $items[] = [
                'label' => $status->label(),
                'href' => $buildUsersUrl([
                    'query' => $state['query'],
                    'filter' => $status->value,
                    'sort' => $state['sort'],
                    'direction' => $state['direction'],
                    'page' => 1,
                ]),
                'active' => $state['filter'] === $status->value,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, string> $columnOptions
     * @return list<array{label:string,key:string,checked:bool}>
     */
    private function buildColumnItems(array $visibleColumns, array $columnOptions): array
    {
        $items = [];

        foreach ($columnOptions as $key => $label) {
            $items[] = [
                'label' => $label,
                'key' => $key,
                'checked' => in_array($key, $visibleColumns, true),
            ];
        }

        return $items;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    private function resetState(array $state): array
    {
        return [
            'query' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => 1,
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{q:string,status:string,sort:string,direction:string}
     */
    private function searchHiddenParams(array $state): array
    {
        return [
            'q' => $state['query'],
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
        ];
    }
}
