<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use Closure;

final class Column
{
    private string $label;
    private string $field;
    private bool $sortable = false;
    private bool $searchable = false;
    private bool $filterable = false;
    private bool $hidden = false;
    private ?string $width = null;
    private string $align = 'left';
    private ?string $sortField = null;

    /** @var null|Closure(mixed):mixed */
    private ?Closure $formatter = null;

    /** @var null|Closure(mixed):string */
    private ?Closure $htmlCallback = null;

    /** @var null|Closure(mixed):mixed */
    private ?Closure $badgeCallback = null;

    /** @var null|Closure(mixed):mixed */
    private ?Closure $iconCallback = null;

    /** @var null|Closure(array|object):string */
    private ?Closure $hrefCallback = null;

    public function __construct(string $field, ?string $label = null)
    {
        $this->field = $field;
        $this->label = $label ?? ucfirst(str_replace('_', ' ', $field));
    }

    public static function make(string $field, ?string $label = null): self
    {
        return new self($field, $label);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function sortable(?string $sortField = null): self
    {
        $this->sortable = true;
        $this->sortField = $sortField;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function filterable(bool $filterable = true): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function width(?string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function align(string $align): self
    {
        $align = strtolower(trim($align));
        $this->align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';

        return $this;
    }

    /**
     * @param Closure(mixed):mixed $formatter
     */
    public function format(Closure $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @param Closure(mixed):string $callback
     */
    public function html(Closure $callback): self
    {
        $this->htmlCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(mixed):mixed $callback
     */
    public function badge(Closure $callback): self
    {
        $this->badgeCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(mixed):mixed $callback
     */
    public function icon(Closure $callback): self
    {
        $this->iconCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(array|object):string $callback
     */
    public function link(Closure $callback): self
    {
        $this->hrefCallback = $callback;

        return $this;
    }

    public function field(): string
    {
        return $this->field;
    }

    public function labelText(): string
    {
        return $this->label;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function sortField(): string
    {
        return $this->sortField ?? $this->field;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->field,
            'field' => $this->field,
            'label' => $this->label,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'filterable' => $this->filterable,
            'hidden' => $this->hidden,
            'width' => $this->width,
            'align' => $this->align,
            'sort_field' => $this->sortField(),
        ];
    }

    /**
     * @param array<string, mixed>|object $row
     * @return array<string, mixed>
     */
    public function toCell(array|object $row): array
    {
        $value = $this->extractValue($row, $this->field);
        $display = $this->formatter !== null ? ($this->formatter)($value) : $value;

        $cell = [
            'field' => $this->field,
            'label' => $this->label,
            'value' => $display,
            'raw' => $value,
            'hidden' => $this->hidden,
            'width' => $this->width,
            'align' => $this->align,
        ];

        if ($this->htmlCallback !== null) {
            $cell['type'] = 'html';
            $cell['html'] = (string) ($this->htmlCallback)($display);

            return $cell;
        }

        if ($this->badgeCallback !== null) {
            $badge = ($this->badgeCallback)($display);
            $cell['type'] = 'badge';
            $cell['badge'] = is_array($badge) ? $badge : ['tone' => (string) $badge];

            return $cell;
        }

        if ($this->iconCallback !== null) {
            $icon = ($this->iconCallback)($display);
            $cell['type'] = 'icon';
            $cell['icon'] = is_array($icon) ? $icon : ['name' => (string) $icon];

            return $cell;
        }

        if ($this->hrefCallback !== null) {
            $cell['href'] = (string) ($this->hrefCallback)($row);
        }

        $cell['type'] = 'text';
        $cell['value'] = is_scalar($display) || $display === null ? (string) $display : $display;

        return $cell;
    }

    /**
     * @param array<string, mixed>|object $row
     */
    private function extractValue(array|object $row, string $path): mixed
    {
        $segments = explode('.', $path);
        $value = $row;

        foreach ($segments as $segment) {
            if (is_array($value)) {
                $value = $value[$segment] ?? null;
                continue;
            }

            if (is_object($value)) {
                if (method_exists($value, 'getAttribute')) {
                    $value = $value->getAttribute($segment);
                    continue;
                }

                if (isset($value->{$segment})) {
                    $value = $value->{$segment};
                    continue;
                }

                if (method_exists($value, '__get')) {
                    $value = $value->{$segment};
                    continue;
                }
            }

            return null;
        }

        return $value;
    }
}
