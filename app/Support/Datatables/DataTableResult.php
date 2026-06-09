<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use App\Support\Datatables\Contracts\DataTableResultInterface;
use App\Support\Datatables\DTO\DataTableAction;
use App\Support\Datatables\DTO\DataTableColumn;
use App\Support\Datatables\DTO\DataTableRow;
use App\Support\Pagination\PaginationResult;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final readonly class DataTableResult implements DataTableResultInterface, ArrayAccess, IteratorAggregate
{
    /**
     * @param array<string, mixed> $features
     * @param array<string, mixed> $toolbar
     * @param array<string, mixed> $bulk
     * @param list<DataTableColumn> $columns
     * @param list<DataTableRow> $rows
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $search
     * @param array<string, mixed> $sort
     * @param list<DataTableAction> $actions
     * @param list<DataTableAction> $bulkActions
     * @param array<string, mixed> $emptyState
     */
    public function __construct(
        private string $title,
        private string $description,
        private array $features,
        private array $toolbar,
        private array $bulk,
        private array $columns,
        private array $rows,
        private PaginationResult $pagination,
        private array $filters,
        private array $search,
        private array $sort,
        private array $actions,
        private array $bulkActions,
        private array $emptyState,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function columns(): array
    {
        return array_map(
            static fn (DataTableColumn $column): array => $column->toArray(),
            $this->columns
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getColumns(): array
    {
        return $this->columns();
    }

    /**
     * @return list<DataTableColumn>
     */
    public function columnObjects(): array
    {
        return $this->columns;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return array_map(
            static fn (DataTableRow $row): array => $row->toArray(),
            $this->rows
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows();
    }

    /**
     * @return list<DataTableRow>
     */
    public function rowObjects(): array
    {
        return $this->rows;
    }

    public function pagination(): PaginationResult
    {
        return $this->pagination;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPagination(): array
    {
        return $this->pagination()->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function paginationArray(): array
    {
        return $this->pagination()->toArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filters(): array
    {
        if (is_array($this->filters['items'] ?? null)) {
            return $this->filters['items'];
        }

        $items = [];
        foreach ($this->filters as $filter) {
            if (is_array($filter)) {
                $items[] = $filter;
            }
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFilters(): array
    {
        return $this->filters();
    }

    /**
     * @return array<string, mixed>
     */
    public function search(): array
    {
        return $this->search;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSearch(): array
    {
        return $this->search();
    }

    /**
     * @return array<string, mixed>
     */
    public function sort(): array
    {
        return $this->sort;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSort(): array
    {
        return $this->sort();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function actions(): array
    {
        return array_map(
            static fn (DataTableAction $action): array => $action->toArray(),
            $this->actions
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActions(): array
    {
        return $this->actions();
    }

    /**
     * @return list<DataTableAction>
     */
    public function actionObjects(): array
    {
        return $this->actions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bulkActions(): array
    {
        return array_map(
            static fn (DataTableAction $action): array => $action->toArray(),
            $this->bulkActions
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getBulkActions(): array
    {
        return $this->bulkActions();
    }

    /**
     * @return list<DataTableAction>
     */
    public function bulkActionObjects(): array
    {
        return $this->bulkActions;
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyState(): array
    {
        return $this->emptyState;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEmptyState(): array
    {
        return $this->emptyState();
    }

    /**
     * @return array<string, mixed>
     */
    public function toolbar(): array
    {
        return $this->toolbar;
    }

    /**
     * @return array<string, mixed>
     */
    public function getToolbar(): array
    {
        return $this->toolbar();
    }

    /**
     * @return array<string, mixed>
     */
    public function bulk(): array
    {
        return $this->bulk;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBulk(): array
    {
        return $this->bulk();
    }

    /**
     * @return array<string, mixed>
     */
    public function features(): array
    {
        return $this->features;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFeatures(): array
    {
        return $this->features();
    }

    public function title(): string
    {
        return $this->title;
    }

    public function getTitle(): string
    {
        return $this->title();
    }

    public function description(): string
    {
        return $this->description;
    }

    public function getDescription(): string
    {
        return $this->description();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->normalizeForOutput([
            'title' => $this->title,
            'description' => $this->description,
            'features' => $this->features,
            'toolbar' => $this->toolbar,
            'bulk' => $this->bulk,
            'columns' => $this->columns(),
            'rows' => $this->rows(),
            'pagination' => $this->pagination,
            'filters' => $this->filters(),
            'search' => $this->search,
            'sort' => $this->sort,
            'actions' => $this->actions(),
            'bulkActions' => $this->bulkActions(),
            'emptyState' => $this->emptyState,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string) $offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        $data = $this->toArray();

        return $data[(string) $offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('DataTableResult is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('DataTableResult is immutable.');
    }

    /**
     * @return Traversable<string, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeForOutput(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value instanceof JsonSerializable) {
                $payload[$key] = $value->jsonSerialize();

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->normalizeForOutput($value);
            }
        }

        return $payload;
    }
}
