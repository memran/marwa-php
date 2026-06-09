<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use App\Support\Datatables\Contracts\DataTableQueryInterface;
use Marwa\DB\Query\Builder as BaseQueryBuilder;
use Marwa\DB\ORM\QueryBuilder;

final class DataTableQuery implements DataTableQueryInterface
{
    public function __construct(private QueryBuilder $builder)
    {
    }

    public function builder(): QueryBuilder
    {
        return $this->builder;
    }

    public function count(): int
    {
        return $this->builder->count();
    }

    /**
     * @return array<int, mixed>
     */
    public function get(): array
    {
        return $this->builder->get();
    }

    /**
     * @return array{data:array<int, mixed>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginate(int $perPage, int $page): array
    {
        return $this->builder->paginate($perPage, $page);
    }

    /**
     * @param list<string> $searchableColumns
     */
    public function applySearch(Search $search, array $searchableColumns): self
    {
        $columns = $search->columnsValue() !== [] ? $search->columnsValue() : $searchableColumns;
        $term = $search->termValue();

        if ($term === '' || $columns === []) {
            return $this;
        }

        $like = '%' . $term . '%';
        $this->builder->where(static function (BaseQueryBuilder $nested) use ($columns, $like): void {
            $first = true;
            foreach ($columns as $column) {
                if ($first) {
                    $nested->where($column, 'like', $like);
                    $first = false;

                    continue;
                }

                $nested->orWhere($column, 'like', $like);
            }
        });

        return $this;
    }

    /**
     * @param list<Filter> $filters
     * @param array<string, mixed> $filterValues
     */
    public function applyFilters(array $filters, array $filterValues): self
    {
        foreach ($filters as $filter) {
            $filter->applyTo($this->builder, $filter->extract($filterValues));
        }

        return $this;
    }

    /**
     * @param list<string> $sortableColumns
     */
    public function applySort(Sort $sort, array $sortableColumns, ?string $fallback = null, ?string $fallbackDirection = null): self
    {
        $column = $sort->fieldValue();
        $direction = $sort->directionValue();

        if ($column === '' || !in_array($column, $sortableColumns, true)) {
            $column = $fallback ?? ($sortableColumns[0] ?? '');
            if ($fallbackDirection !== null) {
                $direction = $fallbackDirection;
            }
        }

        if ($column === '') {
            return $this;
        }

        $this->builder->orderBy($column, $direction);

        return $this;
    }
}
