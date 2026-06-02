<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UsersRequestParams
{
    /**
     * @return array{q:string,status:string,sort:string,direction:string,page:int,columns:mixed}
     */
    public function listParams(): array
    {
        return [
            'q' => request('q', ''),
            'status' => request('status', 'all'),
            'sort' => request('sort', 'created_at'),
            'direction' => request('direction', 'desc'),
            'page' => request('page', 1),
            'columns' => request('columns', []),
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

    public function bulkStatus(): string
    {
        return strtolower(trim((string) request('bulk_status', '')));
    }
}
