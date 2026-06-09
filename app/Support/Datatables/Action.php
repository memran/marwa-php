<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use Closure;

final class Action
{
    private string $label;
    private string $variant = 'secondary';
    private ?string $icon = null;
    private ?string $permission = null;
    private ?string $confirm = null;
    private ?string $href = null;
    private ?string $target = null;
    private ?string $rel = null;
    private ?string $class = null;
    private string $type = 'link';

    /** @var null|Closure(mixed):bool */
    private ?Closure $visibleCallback = null;

    /** @var null|Closure(mixed):string */
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

    public function target(?string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function rel(?string $rel): self
    {
        $this->rel = $rel;

        return $this;
    }

    public function class(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function button(): self
    {
        $this->type = 'button';

        return $this;
    }

    /**
     * @param Closure(mixed):bool $callback
     */
    public function visible(Closure $callback): self
    {
        $this->visibleCallback = $callback;

        return $this;
    }

    /**
     * @param Closure(mixed):string $callback
     */
    public function url(Closure $callback): self
    {
        $this->hrefCallback = $callback;

        return $this;
    }

    public function href(string $href): self
    {
        $this->href = $href;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(mixed $row = null): ?array
    {
        if ($this->visibleCallback !== null && $row !== null && !(bool) ($this->visibleCallback)($row)) {
            return null;
        }

        $href = $this->href;
        if ($this->hrefCallback !== null && $row !== null) {
            $href = (string) ($this->hrefCallback)($row);
        }

        $action = [
            'name' => $this->name,
            'type' => $this->type,
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

        if ($this->target !== null) {
            $action['target'] = $this->target;
        }

        if ($this->rel !== null) {
            $action['rel'] = $this->rel;
        }

        if ($this->class !== null) {
            $action['class'] = $this->class;
        }

        return $action;
    }
}
