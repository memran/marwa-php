<?php

declare(strict_types=1);

namespace App\Support\DataTable;

final class DataTableColumns
{
    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param array<string, string> $columnOptions
     * @param list<string> $sortableKeys
     * @return list<array<string, mixed>>
     */
    public function build(
        array $state,
        array $visibleColumns,
        array $columnOptions,
        array $sortableKeys,
        callable $buildUrl
    ): array {
        $columns = [];

        foreach ($columnOptions as $key => $label) {
            if (in_array($key, $visibleColumns, true)) {
                $columns[] = $this->buildColumn($key, $label, $state, $buildUrl, $sortableKeys);
            }
        }

        return $columns;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $sortableKeys
     * @return array<string, mixed>
     */
    public function buildColumn(
        string $key,
        string $label,
        array $state,
        callable $buildUrl,
        array $sortableKeys
    ): array {
        $isSortable = in_array($key, $sortableKeys, true);

        return [
            'key' => $key,
            'label' => $label,
            'sortable' => $isSortable,
            'active' => $state['sort'] === $key,
            'href' => $isSortable ? $buildUrl($this->sortToggleState($state, $key)) : null,
            'sort_direction' => $state['sort'] === $key ? $state['direction'] : 'desc',
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function sortToggleState(array $state, string $key): array
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
}
