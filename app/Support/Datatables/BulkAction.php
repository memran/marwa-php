<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use Closure;

final class BulkAction
{
    private string $label;
    private string $variant = 'secondary';
    private ?string $icon = null;
    private ?string $permission = null;
    private ?string $confirm = null;
    private ?string $href = null;

    /** @var null|Closure(array<int, mixed>):bool */
    private ?Closure $visibleCallback = null;

    /** @var null|Closure(array<int, mixed>):string */
    private ?Closure $hrefCallback = null;

    private string $name;

    public static function make(string $name): self
    {
        return new self($name);
    }

    private function __construct(string $name)
    {
        $this->name = $name;
        $this->label = ucfirst(str_replace(['_', '-'], ' ', $name));
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function variant(string $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function permission(?string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function confirm(?string $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function href(string $href): self
    {
        $this->href = $href;

        return $this;
    }

    /**
     * @param Closure(array<int, mixed>):bool $callback
     */
    public function visible(Closure $callback): self
    {
        $this->visibleCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(array<int, mixed>):string $callback
     */
    public function url(Closure $callback): self
    {
        $this->hrefCallback = $callback;

        return $this;
    }

    /**
     * @param array<int, mixed> $selectedIds
     * @return array<string, mixed>|null
     */
    public function resolve(array $selectedIds = []): ?array
    {
        if ($this->visibleCallback !== null && !(bool) ($this->visibleCallback)($selectedIds)) {
            return null;
        }

        if ($this->permission !== null && $this->permission !== '' && function_exists('can') && !can($this->permission)) {
            return null;
        }

        $href = $this->href;
        if ($this->hrefCallback !== null) {
            $href = (string) ($this->hrefCallback)($selectedIds);
        }

        $action = [
            'name' => $this->name,
            'type' => 'bulk',
            'label' => $this->label,
            'variant' => $this->variant,
        ];

        if ($href !== null) {
            $action['href'] = $href;
        }

        if ($this->icon !== null) {
            $action['icon'] = $this->icon;
        }

        if ($this->permission !== null) {
            $action['permission'] = $this->permission;
        }

        if ($this->confirm !== null) {
            $action['confirm'] = $this->confirm;
        }

        return $action;
    }
}
