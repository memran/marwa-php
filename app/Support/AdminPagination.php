<?php

declare(strict_types=1);

namespace App\Support;

final class AdminPagination
{
    /**
     * @param array{total:int,per_page:int,current_page:int,last_page:int} $pagination
     * @param array<string, scalar|null> $params
     * @return array{
     *     summary:string,
     *     links:list<array{page:int,url:string,active:bool}>
     * }
     */
    public function viewData(array $pagination, string $path, array $params = [], string $pageParam = 'page'): array
    {
        $currentPage = max(1, (int) $pagination['current_page']);
        $lastPage = max(1, (int) $pagination['last_page']);
        $total = max(0, (int) $pagination['total']);
        $perPage = max(1, (int) $pagination['per_page']);
        $from = $total === 0 ? 0 : (($currentPage - 1) * $perPage) + 1;
        $to = min($total, $currentPage * $perPage);
        $summary = $total === 0
            ? 'Showing 0 results'
            : sprintf('Showing %d-%d of %d results', $from, $to, $total);

        $cleanParams = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $cleanParams[$key] = $value;
        }

        $links = [];
        $window = 2;
        $pages = [$currentPage];

        for ($offset = 1; $offset <= $window; $offset++) {
            $pages[] = $currentPage - $offset;
            $pages[] = $currentPage + $offset;
        }

        $pages[] = 1;
        $pages[] = $lastPage;
        $pages = array_values(array_unique(array_filter(
            $pages,
            static fn (int $page) => $page >= 1 && $page <= $lastPage
        )));
        sort($pages);

        foreach ($pages as $page) {
            $query = http_build_query(array_replace($cleanParams, [$pageParam => $page]));
            $links[] = [
                'page' => $page,
                'url' => $path . ($query !== '' ? '?' . $query : ''),
                'active' => $page === $currentPage,
            ];
        }

        return [
            'summary' => $summary,
            'links' => $links,
        ];
    }
}
