<?php

declare(strict_types=1);

namespace App\Support\Datatables\DTO;

use JsonSerializable;

/**
 * @property-read string $key
 * @property-read mixed $value
 * @property-read string $type
 * @property-read ?string $href
 * @property-read mixed $raw
 * @property-read mixed $align
 * @property-read mixed $hidden
 * @property-read mixed $width
 * @property-read mixed $badge
 * @property-read mixed $icon
 * @property-read mixed $avatar
 * @property-read mixed $meta
 * @property-read mixed $muted
 * @property-read mixed $tone
 * @property-read mixed $items
 * @property-read mixed $html
 */
final readonly class DataTableCell implements JsonSerializable
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
        return (string) ($this->payload['field'] ?? $this->payload['key'] ?? '');
    }

    public function value(): mixed
    {
        return $this->payload['value'] ?? null;
    }

    public function type(): string
    {
        return (string) ($this->payload['type'] ?? 'text');
    }

    public function href(): ?string
    {
        $href = $this->payload['href'] ?? null;

        return is_string($href) && $href !== '' ? $href : null;
    }

    public function raw(): mixed
    {
        return $this->payload['raw'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->payload;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'key' => $this->key(),
            'value' => $this->value(),
            'type' => $this->type(),
            'href' => $this->href(),
            'raw' => $this->raw(),
            default => $this->payload[$name] ?? null,
        };
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'key', 'value', 'type', 'href', 'raw' => true,
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
