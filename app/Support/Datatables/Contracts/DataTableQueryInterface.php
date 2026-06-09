<?php

declare(strict_types=1);

namespace App\Support\Datatables\Contracts;

use Marwa\DB\ORM\QueryBuilder;

interface DataTableQueryInterface
{
    public function builder(): QueryBuilder;

    /**
     * @return array{data:array<int, mixed>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginate(int $perPage, int $page): array;

    /**
     * @return array<int, mixed>
     */
    public function get(): array;

    public function count(): int;
}
