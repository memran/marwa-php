<?php

declare(strict_types=1);

namespace App\Support\Datatables\Contracts;

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
     * @return list<array<string, mixed>>
     */
    public function rows(): array;

    /**
     * @return array<string, mixed>
     */
    public function pagination(): array;

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
     * @return list<array<string, mixed>>
     */
    public function bulkActions(): array;

    /**
     * @return array<string, mixed>
     */
    public function emptyState(): array;
}
