<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Pagination\PaginationResult;

final class AdminPagination
{
    /**
     * @param array{data:array<int, mixed>, total:int, per_page:int, current_page:int, last_page:int} $source
     * @param array<string, mixed> $query
     */
    public function viewData(
        array $source,
        string $path,
        array $query = [],
        string $pageName = 'page',
        int $window = 2,
        int $maxPerPage = 100
    ): PaginationResult {
        return PaginationResult::fromArray($source, $path, $query, $pageName, $window, $maxPerPage);
    }
}
