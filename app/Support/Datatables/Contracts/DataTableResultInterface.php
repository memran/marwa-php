<?php

declare(strict_types=1);

namespace App\Support\Datatables\Contracts;

use App\Support\Datatables\DTO\DataTableAction;
use App\Support\Datatables\DTO\DataTableCell;
use App\Support\Datatables\DTO\DataTableColumn;
use App\Support\Datatables\DTO\DataTableRow;
use App\Support\Pagination\PaginationResult;

interface DataTableResultInterface extends \JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function columns(): array;

    /**
     * @return list<DataTableColumn>
     */
    public function columnObjects(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array;

    /**
     * @return list<DataTableRow>
     */
    public function rowObjects(): array;

    /**
     * @return PaginationResult
     */
    public function pagination(): PaginationResult;

    /**
     * @return array<string, mixed>
     */
    public function paginationArray(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function filters(): array;

    /**
     * @return array<string, mixed>
     */
    public function search(): array;

    /**
     * @return array<string, mixed>
     */
    public function sort(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function actions(): array;

    /**
     * @return list<DataTableAction>
     */
    public function actionObjects(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function bulkActions(): array;

    /**
     * @return list<DataTableAction>
     */
    public function bulkActionObjects(): array;

    /**
     * @return array<string, mixed>
     */
    public function emptyState(): array;

    /**
     * @return array<string, mixed>
     */
    public function toolbar(): array;

    /**
     * @return array<string, mixed>
     */
    public function bulk(): array;

    /**
     * @return array<string, mixed>
     */
    public function features(): array;

    public function title(): string;

    public function description(): string;
}
