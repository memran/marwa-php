<?php

declare(strict_types=1);

namespace App\Support\DataTable;

final class DataTableToolbar
{
    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    public function buildSearch(
        string $basePath,
        array $state,
        array $visibleColumns,
        callable $buildUrl,
        callable $hiddenFields,
        string $placeholder,
        string $ariaLabel
    ): array {
        return [
            'action' => $basePath,
            'value' => $state['query'],
            'placeholder' => $placeholder,
            'aria_label' => $ariaLabel,
            'submit_label' => $ariaLabel,
            'clear_label' => 'Clear search',
            'clear_url' => $buildUrl($this->clearedQueryState($state)),
            'hidden_fields' => $hiddenFields($this->searchFormHiddenParams($state), $visibleColumns),
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param list<array{label:string,href:string,active:bool}> $filterItems
     * @return array<string, mixed>
     */
    public function buildFilter(
        array $state,
        array $visibleColumns,
        callable $buildUrl,
        array $filterItems
    ): array {
        return [
            'label' => 'Filters',
            'current_label' => $this->currentFilterLabel($state, $filterItems),
            'items' => $filterItems,
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param array<string, string> $columnOptions
     * @return array<string, mixed>
     */
    public function buildColumnsToolbar(
        string $basePath,
        array $state,
        array $visibleColumns,
        array $columnOptions,
        callable $buildUrl,
        callable $hiddenFields
    ): array {
        return [
            'label' => 'Columns',
            'legend' => 'Visible columns',
            'visible_count' => count($visibleColumns),
            'action' => $basePath,
            'reset_url' => $buildUrl($this->resetState($state), array_keys($columnOptions)),
            'hidden_fields' => $hiddenFields($this->searchHiddenParams($state)),
            'items' => $this->buildColumnItems($visibleColumns, $columnOptions),
            'submit_label' => 'Apply',
            'reset_label' => 'Reset',
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<array<string, mixed>> $rows
     * @param list<string> $visibleColumns
     * @param list<array{value:string,label:string}> $statusOptions
     * @param array<string, string> $bulkConfig
     * @return array<string, mixed>
     */
    public function buildBulk(
        string $formId,
        string $deleteUrl,
        string $statusUrl,
        array $state,
        array $rows,
        array $visibleColumns,
        array $statusOptions,
        callable $hiddenFields,
        array $bulkConfig = []
    ): array {
        $config = array_merge([
            'select_all_label' => 'Select all',
            'selection_header_label' => 'Select rows',
            'status_placeholder' => 'Bulk status',
            'status_label' => 'Update status',
            'status_icon' => 'refresh-cw',
            'delete_label' => 'Delete selected',
            'delete_icon' => 'trash-2',
            'delete_confirm' => 'Delete the selected rows?',
        ], $bulkConfig);

        return [
            'form_id' => $formId,
            'action_delete_url' => $deleteUrl,
            'action_status_url' => $statusUrl,
            'selectable_count' => $this->countSelectable($rows),
            'select_all_label' => $config['select_all_label'],
            'selection_header_label' => $config['selection_header_label'],
            'status_placeholder' => $config['status_placeholder'],
            'status_label' => $config['status_label'],
            'status_icon' => $config['status_icon'],
            'status_options' => $statusOptions,
            'delete_label' => $config['delete_label'],
            'delete_icon' => $config['delete_icon'],
            'delete_confirm' => $config['delete_confirm'],
            'hidden_fields' => $hiddenFields($this->bulkHiddenParams($state), $visibleColumns),
        ];
    }

    /**
     * @param array<string, string|int|list<string>|null> $params
     * @param list<string> $visibleColumns
     * @return list<array{name:string,value:string}>
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
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function clearedQueryState(array $state): array
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
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function resetState(array $state): array
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
     * @param list<array<string, mixed>> $rows
     */
    public function countSelectable(array $rows): int
    {
        return count(array_filter(
            $rows,
            static fn (array $row): bool => !($row['bulk']['disabled'] ?? false)
        ));
    }

    /**
     * @param list<string> $visibleColumns
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
     * @param list<array{label:string,href:string,active:bool}> $filterItems
     */
    private function currentFilterLabel(array $state, array $filterItems): string
    {
        foreach ($filterItems as $item) {
            if ($item['active']) {
                return $item['label'];
            }
        }
        return ucfirst(str_replace('_', ' ', $state['filter']));
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
}
