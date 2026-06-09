<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use App\Support\Datatables\Contracts\DataTableResultInterface;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class DataTableResult implements DataTableResultInterface, ArrayAccess, IteratorAggregate
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function columns(): array
    {
        return $this->payload['columns'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getColumns(): array
    {
        return $this->columns();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->payload['rows'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows();
    }

    /**
     * @return array<string, mixed>
     */
    public function pagination(): array
    {
        return $this->payload['pagination'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPagination(): array
    {
        return $this->pagination();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filters(): array
    {
        return $this->payload['filters'] ?? [];
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
        return $this->payload['search'] ?? [];
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
        return $this->payload['sort'] ?? [];
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
        return $this->payload['actions'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActions(): array
    {
        return $this->actions();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bulkActions(): array
    {
        return $this->payload['bulkActions'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getBulkActions(): array
    {
        return $this->bulkActions();
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyState(): array
    {
        return $this->payload['emptyState'] ?? [];
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
        return $this->payload['toolbar'] ?? [];
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
        return $this->payload['bulk'] ?? [];
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
        return $this->payload['features'] ?? [];
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
        return (string) ($this->payload['title'] ?? '');
    }

    public function getTitle(): string
    {
        return $this->title();
    }

    public function description(): string
    {
        return (string) ($this->payload['description'] ?? '');
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
        return $this->payload;
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
        return array_key_exists((string) $offset, $this->payload);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->payload[(string) $offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('DataTableResult is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('DataTableResult is immutable.');
    }

    /**
     * @return Traversable<string, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->payload);
    }
}
