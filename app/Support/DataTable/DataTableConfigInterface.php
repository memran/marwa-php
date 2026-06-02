<?php

declare(strict_types=1);

namespace App\Support\DataTable;

interface DataTableConfigInterface
{
    public function pageTitle(): string;

    public function pageDescription(): string;

    public function searchPlaceholder(): string;

    /**
     * @return array<string, string>
     */
    public function columnOptions(): array;

    /**
     * @return list<string>
     */
    public function sortableKeys(): array;

    public function basePath(): string;

    public function defaultSort(): string;

    public function defaultDirection(): string;

    public function defaultFilter(): string;

    public function rowKey(mixed $row): string;

    public function rowIsTrashed(mixed $row): bool;

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array{label:string,href:string,active:bool}>
     */
    public function filterItems(array $state, array $visibleColumns, callable $buildUrl): array;

    /**
     * @return list<array{value:string,label:string}>
     */
    public function statusOptions(): array;

    /**
     * @param array<string, string|int|list<string>|null> $params
     * @param list<string> $visibleColumns
     * @return list<array{name:string,value:string}>
     */
    public function hiddenFields(array $params, array $visibleColumns): array;

    /**
     * @return array{bulk:array<string, mixed>, cells:array<string, array<string, mixed>>, actions:list<array<string, mixed>>}
     */
    public function buildRow(mixed $row): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function buildRowActions(mixed $row, bool $isTrashed, bool $isProtected): array;

    /**
     * @return array<string, mixed>
     */
    public function rowBulkMeta(mixed $row, bool $isProtected, bool $isTrashed, bool $isActiveSession): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildCells(mixed $row, bool $isProtected): array;

    /**
     * @return list<\App\Support\Export\Column>
     */
    public function buildExportColumns(): array;

    /**
     * @param list<string> $visibleKeys
     * @return list<\App\Support\Export\Column>
     */
    public function resolveExportColumns(array $visibleKeys): array;

    /**
     * @return list<array{label:string,url:string,icon:string,format:string,variant:string}>
     */
    public function exports(): array;
}
