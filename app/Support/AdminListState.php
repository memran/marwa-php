<?php

declare(strict_types=1);

namespace App\Support;

final class AdminListState
{
    /**
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function state(
        string $queryParam = 'q',
        string $filterParam = 'filter',
        string $sortParam = 'sort',
        string $directionParam = 'direction',
        string $pageParam = 'page'
    ): array {
        return $this->stateFrom([
            $queryParam => request($queryParam, ''),
            $filterParam => request($filterParam, 'all'),
            $sortParam => request($sortParam, 'created_at'),
            $directionParam => request($directionParam, 'desc'),
            $pageParam => request($pageParam, 1),
        ], $queryParam, $filterParam, $sortParam, $directionParam, $pageParam);
    }

    public function direction(string $directionParam = 'direction'): string
    {
        $direction = strtolower(trim((string) request($directionParam, 'desc')));

        return in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
    }

    /**
     * @param array<string, mixed> $params
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function stateFrom(
        array $params,
        string $queryParam = 'q',
        string $filterParam = 'filter',
        string $sortParam = 'sort',
        string $directionParam = 'direction',
        string $pageParam = 'page'
    ): array {
        $sort = trim((string) ($params[$sortParam] ?? 'created_at'));
        $direction = strtolower(trim((string) ($params[$directionParam] ?? 'desc')));

        return [
            'query' => trim((string) ($params[$queryParam] ?? '')),
            'filter' => trim((string) ($params[$filterParam] ?? 'all')),
            'sort' => $sort !== '' ? $sort : 'created_at',
            'direction' => in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc',
            'page' => max(1, (int) ($params[$pageParam] ?? 1)),
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    public function paginationParams(array $state, array $extra = []): array
    {
        return array_filter(array_merge([
            'q' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
        ], $extra), static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
