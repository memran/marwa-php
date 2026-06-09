<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use App\Support\Datatables\Contracts\DataTableResultInterface;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

final class DataTableResult implements DataTableResultInterface, ArrayAccess, IteratorAggregate
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    public function columns(): array
    {
        return $this->payload['columns'] ?? [];
    }

    public function getColumns(): array
    {
        return $this->columns();
    }

    public function rows(): array
    {
        return $this->payload['rows'] ?? [];
    }

    public function getRows(): array
    {
        return $this->rows();
    }

    public function pagination(): array
    {
        return $this->payload['pagination'] ?? [];
    }

    public function getPagination(): array
    {
        return $this->pagination();
    }

    public function filters(): array
    {
        return $this->payload['filters'] ?? [];
    }

    public function getFilters(): array
    {
        return $this->filters();
    }

    public function search(): array
    {
        return $this->payload['search'] ?? [];
    }

    public function getSearch(): array
    {
        return $this->search();
    }

    public function sort(): array
    {
        return $this->payload['sort'] ?? [];
    }

    public function getSort(): array
    {
        return $this->sort();
    }

    public function actions(): array
    {
        return $this->payload['actions'] ?? [];
    }

    public function getActions(): array
    {
        return $this->actions();
    }

    public function bulkActions(): array
    {
        return $this->payload['bulkActions'] ?? [];
    }

    public function getBulkActions(): array
    {
        return $this->bulkActions();
    }

    public function emptyState(): array
    {
        return $this->payload['emptyState'] ?? [];
    }

    public function getEmptyState(): array
    {
        return $this->emptyState();
    }

    public function toolbar(): array
    {
        return $this->payload['toolbar'] ?? [];
    }

    public function getToolbar(): array
    {
        return $this->toolbar();
    }

    public function bulk(): array
    {
        return $this->payload['bulk'] ?? [];
    }

    public function getBulk(): array
    {
        return $this->bulk();
    }

    public function features(): array
    {
        return $this->payload['features'] ?? [];
    }

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

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->payload);
    }
}
