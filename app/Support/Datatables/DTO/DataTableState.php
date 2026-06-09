<?php

declare(strict_types=1);

namespace App\Support\Datatables\DTO;

use JsonSerializable;

/**
 * @property-read string $search
 * @property-read string $sort
 * @property-read string $direction
 * @property-read int $page
 * @property-read array<string, mixed> $filters
 * @property-read list<string>|null $columns
 */
final readonly class DataTableState implements JsonSerializable
{
    /**
     * @param array<string, mixed> $filters
     * @param list<string>|null $columns
     */
    public function __construct(
        private string $search,
        private string $sort,
        private string $direction,
        private int $page,
        private array $filters,
        private ?array $columns,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, string $defaultSortField = '', string $defaultSortDirection = 'asc'): self
    {
        $search = trim((string) ($payload['search'] ?? ''));
        $sort = trim((string) ($payload['sort'] ?? ''));
        $direction = strtolower(trim((string) ($payload['direction'] ?? 'asc')));
        $page = max(1, (int) ($payload['page'] ?? 1));
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $columns = self::normalizeColumns($payload['columns'] ?? null);

        if ($sort === '' && $defaultSortField !== '') {
            $sort = $defaultSortField;
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultSortDirection === 'desc' ? 'desc' : 'asc';
        }

        return new self($search, $sort, $direction, $page, $filters, $columns);
    }

    public function search(): string
    {
        return $this->search;
    }

    public function sort(): string
    {
        return $this->sort;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function page(): int
    {
        return $this->page;
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @return list<string>|null
     */
    public function columns(): ?array
    {
        return $this->columns;
    }

    /**
     * @param string $searchParameter
     * @param string $sortParameter
     * @param string $directionParameter
     * @param string $filterParameter
     * @param string $columnsParameter
     * @return array<string, mixed>
     */
    public function paginationQuery(
        string $searchParameter,
        string $sortParameter,
        string $directionParameter,
        string $filterParameter,
        string $columnsParameter
    ): array {
        $query = [
            $searchParameter => $this->search,
            $sortParameter => $this->sort,
            $directionParameter => $this->direction,
            $filterParameter => $this->filters,
        ];

        if ($this->columns !== null) {
            $query[$columnsParameter] = $this->columns;
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'page' => $this->page,
            'filters' => $this->filters,
            'columns' => $this->columns,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param mixed $columns
     * @return list<string>|null
     */
    private static function normalizeColumns(mixed $columns): ?array
    {
        if ($columns === null) {
            return null;
        }

        if (!is_array($columns)) {
            return null;
        }

        $normalized = [];
        foreach ($columns as $column) {
            if (is_string($column) && $column !== '') {
                $normalized[] = $column;
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
