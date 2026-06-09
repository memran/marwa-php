<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use Closure;
use Marwa\DB\ORM\QueryBuilder;

final class Filter
{
    private string $label;
    private string $type;
    /** @var array<string, string> */
    private array $options = [];
    private ?string $placeholder = null;
    private mixed $default = null;

    /** @var null|Closure(QueryBuilder,mixed,self):void */
    private ?Closure $callback = null;

    /** @var null|Closure(self):bool */
    private ?Closure $visibleCallback = null;

    private function __construct(private string $name, string $type)
    {
        $this->type = $type;
        $this->label = ucfirst(str_replace(['_', '-'], ' ', $name));
    }

    public static function text(string $name): self
    {
        return new self($name, 'text');
    }

    public static function select(string $name): self
    {
        return new self($name, 'select');
    }

    public static function boolean(string $name): self
    {
        return new self($name, 'boolean');
    }

    public static function date(string $name): self
    {
        return new self($name, 'date');
    }

    public static function dateRange(string $name): self
    {
        return new self($name, 'date_range');
    }

    public static function numberRange(string $name): self
    {
        return new self($name, 'number_range');
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param array<string, string> $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }

    /**
     * @param Closure(QueryBuilder,mixed,self):void $callback
     */
    public function apply(Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @param Closure(self):bool $callback
     */
    public function visible(Closure $callback): self
    {
        $this->visibleCallback = $callback;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function typeValue(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, string>
     */
    public function optionsValue(): array
    {
        return $this->options;
    }

    public function labelValue(): string
    {
        return $this->label;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function value(array $filters): mixed
    {
        if (array_key_exists($this->name, $filters)) {
            return $filters[$this->name];
        }

        return $this->default;
    }

    public function isVisible(): bool
    {
        return $this->visibleCallback === null || (bool) ($this->visibleCallback)($this);
    }

    public function isActive(mixed $value): bool
    {
        if (is_array($value)) {
            return array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== '') !== [];
        }

        return $value !== null && $value !== '';
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function extract(array $filters): mixed
    {
        if (array_key_exists($this->name, $filters)) {
            return $filters[$this->name];
        }

        if ($this->default !== null) {
            return $this->default;
        }

        return null;
    }

    public function applyTo(QueryBuilder $query, mixed $value): void
    {
        if ($this->callback !== null) {
            ($this->callback)($query, $value, $this);

            return;
        }

        if ($value === null || $value === '' || $value === []) {
            return;
        }

        match ($this->type) {
            'select' => $query->where($this->name, '=', $value),
            'text' => $query->where($this->name, 'like', '%' . trim((string) $value) . '%'),
            'boolean' => $query->where($this->name, '=', $this->normalizeBoolean($value) ? 1 : 0),
            'date' => $query->where($this->name, '=', (string) $value),
            'date_range' => $this->applyDateRange($query, $value),
            'number_range' => $this->applyNumberRange($query, $value),
            default => $query->where($this->name, '=', $value),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $value = null): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options,
            'placeholder' => $this->placeholder,
            'value' => $value ?? $this->default,
            'active' => $this->isActive($value ?? $this->default),
        ];
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed>|list<mixed>|mixed $value
     */
    private function applyDateRange(QueryBuilder $query, mixed $value): void
    {
        $range = is_array($value) ? $value : [];
        $from = trim((string) ($range['from'] ?? $range[0] ?? ''));
        $to = trim((string) ($range['to'] ?? $range[1] ?? ''));

        if ($from !== '' && $to !== '') {
            $query->whereBetween($this->name, [$from, $to]);

            return;
        }

        if ($from !== '') {
            $query->where($this->name, '>=', $from);
        }

        if ($to !== '') {
            $query->where($this->name, '<=', $to);
        }
    }

    /**
     * @param array<string, mixed>|list<mixed>|mixed $value
     */
    private function applyNumberRange(QueryBuilder $query, mixed $value): void
    {
        $range = is_array($value) ? $value : [];
        $from = $range['from'] ?? $range[0] ?? null;
        $to = $range['to'] ?? $range[1] ?? null;

        if ($from !== null && $from !== '' && $to !== null && $to !== '') {
            $query->whereBetween($this->name, [(float) $from, (float) $to]);

            return;
        }

        if ($from !== null && $from !== '') {
            $query->where($this->name, '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->where($this->name, '<=', $to);
        }
    }
}
