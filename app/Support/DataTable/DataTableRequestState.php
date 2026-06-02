<?php

declare(strict_types=1);

namespace App\Support\DataTable;

use App\Support\AdminListState;

final class DataTableRequestState
{
    public function __construct(private readonly AdminListState $listState)
    {
    }

    /**
     * @param array<string, mixed> $requestParams
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function resolve(array $requestParams): array
    {
        $state = $this->listState->stateFrom($requestParams, 'q', 'status', 'sort', 'direction', 'page');

        return [
            'query' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => $state['page'],
        ];
    }

    /**
     * @return list<int>
     */
    public function bulkSelectedIds(): array
    {
        $ids = request('ids', []);

        if (!is_array($ids)) {
            return [];
        }

        return $this->normalizeIds($ids);
    }

    public function bulkStatus(): string
    {
        return strtolower(trim((string) request('bulk_status', '')));
    }

    /**
     * @param list<int|string> $ids
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        $selected = [];

        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $normalized = (int) $id;
                if ($normalized > 0 && !in_array($normalized, $selected, true)) {
                    $selected[] = $normalized;
                }
            }
        }

        return $selected;
    }
}
