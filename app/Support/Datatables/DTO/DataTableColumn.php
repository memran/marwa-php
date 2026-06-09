<?php

declare(strict_types=1);

namespace App\Support\Datatables\DTO;

use JsonSerializable;

/**
 * @property-read string $key
 * @property-read string $field
 * @property-read string $label
 * @property-read bool $sortable
 * @property-read bool $searchable
 * @property-read bool $filterable
 * @property-read bool $hidden
 * @property-read ?string $width
 * @property-read string $align
 * @property-read ?string $href
 * @property-read bool $active
 * @property-read string $sort_direction
 */
final readonly class DataTableColumn implements JsonSerializable
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    public function key(): string
    {
        return (string) ($this->payload['key'] ?? $this->payload['field'] ?? '');
    }

    public function field(): string
    {
        return (string) ($this->payload['field'] ?? '');
    }

    public function label(): string
    {
        return (string) ($this->payload['label'] ?? '');
    }

    public function sortable(): bool
    {
        return (bool) ($this->payload['sortable'] ?? false);
    }

    public function searchable(): bool
    {
        return (bool) ($this->payload['searchable'] ?? false);
    }

    public function filterable(): bool
    {
        return (bool) ($this->payload['filterable'] ?? false);
    }

    public function hidden(): bool
    {
        return (bool) ($this->payload['hidden'] ?? false);
    }

    public function href(): ?string
    {
        $href = $this->payload['href'] ?? null;

        return is_string($href) && $href !== '' ? $href : null;
    }

    public function active(): bool
    {
        return (bool) ($this->payload['active'] ?? false);
    }

    public function sort_direction(): string
    {
        return (string) ($this->payload['sort_direction'] ?? 'asc');
    }

    public function width(): ?string
    {
        $width = $this->payload['width'] ?? null;

        return is_string($width) && $width !== '' ? $width : null;
    }

    public function align(): string
    {
        return (string) ($this->payload['align'] ?? 'left');
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'key' => $this->key(),
            'field' => $this->field(),
            'label' => $this->label(),
            'sortable' => $this->sortable(),
            'searchable' => $this->searchable(),
            'filterable' => $this->filterable(),
            'hidden' => $this->hidden(),
            'width' => $this->width(),
            'align' => $this->align(),
            'href' => $this->href(),
            'active' => $this->active(),
            'sort_direction' => $this->sort_direction(),
            default => $this->payload[$name] ?? null,
        };
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'key', 'field', 'label', 'sortable', 'searchable', 'filterable', 'hidden', 'align', 'active', 'sort_direction' => true,
            'width', 'href' => array_key_exists($name, $this->payload),
            default => array_key_exists($name, $this->payload),
        };
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
}
