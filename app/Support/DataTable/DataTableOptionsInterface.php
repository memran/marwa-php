<?php

declare(strict_types=1);

namespace App\Support\DataTable;

interface DataTableOptionsInterface
{
    /**
     * @return array<string, bool>
     */
    public function features(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function toolbarActions(): array;

    public function bulkDeletePath(): ?string;

    public function bulkStatusPath(): ?string;

    /**
     * @return array<string, string>
     */
    public function emptyState(): array;

    /**
     * @return array{query:string,filter:string,sort:string,direction:string,page:string}
     */
    public function queryParams(): array;
}
