<?php

declare(strict_types=1);

namespace App\Support;

trait PaginatesRows
{
    /**
     * Return only the current page rows, not pagination metadata.
     *
     * @return list<static>
     */
    public static function paginate(int $perPage = 15, int $page = 1): array
    {
        $pageData = parent::paginate(max(1, $perPage), max(1, $page));
        return array_values($pageData['data']);
    }
}
