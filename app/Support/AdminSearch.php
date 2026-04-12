<?php

declare(strict_types=1);

namespace App\Support;

final class AdminSearch
{
    /**
     * @return array{query:string,page:int}
     */
    public function state(string $queryParam = 'q', string $pageParam = 'page'): array
    {
        return [
            'query' => $this->query($queryParam),
            'page' => $this->page($pageParam),
        ];
    }

    public function query(string $queryParam = 'q'): string
    {
        return trim((string) request($queryParam, ''));
    }

    public function page(string $pageParam = 'page'): int
    {
        return max(1, (int) request($pageParam, 1));
    }

    /**
     * @param object $builder
     * @param list<string> $columns
     */
    public function applyLikeFilters(object $builder, string $query, array $columns): void
    {
        $query = trim($query);
        $columns = array_values(array_filter($columns, static fn (string $column): bool => trim($column) !== ''));

        if ($query === '' || $columns === []) {
            return;
        }

        $like = '%' . $query . '%';
        $first = true;

        foreach ($columns as $column) {
            if ($first) {
                $builder->where($column, 'like', $like);
                $first = false;
                continue;
            }

            $builder->orWhere($column, 'like', $like);
        }
    }
}
