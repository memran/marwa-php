<?php

declare(strict_types=1);

namespace App\Support\Datatables\DTO;

use JsonSerializable;

/**
 * @property-read string $key
 * @property-read array<string, DataTableCell> $cells
 * @property-read list<DataTableAction> $actions
 * @property-read array<string, mixed> $bulk
 */
final readonly class DataTableRow implements JsonSerializable
{
    /**
     * @param array<string, DataTableCell> $cells
     * @param list<DataTableAction> $actions
     * @param array<string, mixed> $bulk
     */
    public function __construct(
        private string $key,
        private array $cells,
        private array $actions = [],
        private array $bulk = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $cells = [];
        foreach (($payload['cells'] ?? []) as $field => $cell) {
            if (is_string($field) && is_array($cell)) {
                $cells[$field] = DataTableCell::fromArray($cell);
            }
        }

        $actions = [];
        foreach (($payload['actions'] ?? []) as $action) {
            if (is_array($action)) {
                $actions[] = DataTableAction::fromArray($action);
            }
        }

        return new self(
            (string) ($payload['key'] ?? ''),
            $cells,
            $actions,
            is_array($payload['bulk'] ?? null) ? $payload['bulk'] : []
        );
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return array<string, DataTableCell>
     */
    public function cells(): array
    {
        return $this->cells;
    }

    /**
     * @return list<DataTableAction>
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * @return array<string, mixed>
     */
    public function bulk(): array
    {
        return $this->bulk;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'key' => $this->key(),
            'cells' => $this->cells(),
            'actions' => $this->actions(),
            'bulk' => $this->bulk(),
            default => null,
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['key', 'cells', 'actions', 'bulk'], true);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'bulk' => $this->bulk,
            'cells' => array_map(
                static fn (DataTableCell $cell): array => $cell->toArray(),
                $this->cells
            ),
            'actions' => array_map(
                static fn (DataTableAction $action): array => $action->toArray(),
                $this->actions
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
