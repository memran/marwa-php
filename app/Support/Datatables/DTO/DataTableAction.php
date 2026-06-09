<?php

declare(strict_types=1);

namespace App\Support\Datatables\DTO;

use JsonSerializable;

/**
 * @property-read string $name
 * @property-read string $label
 * @property-read ?string $href
 * @property-read string $variant
 * @property-read string $type
 * @property-read ?string $icon
 * @property-read ?string $permission
 * @property-read ?string $confirm
 * @property-read ?string $target
 * @property-read ?string $rel
 * @property-read ?string $class
 * @property-read bool $disabled
 * @property-read ?string $title
 * @property-read ?string $action
 * @property-read ?string $method
 * @property-read ?string $button_type
 * @property-read ?string $onclick
 */
final readonly class DataTableAction implements JsonSerializable
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

    public function name(): string
    {
        return (string) ($this->payload['name'] ?? '');
    }

    public function label(): string
    {
        return (string) ($this->payload['label'] ?? '');
    }

    public function href(): ?string
    {
        $href = $this->payload['href'] ?? null;

        return is_string($href) && $href !== '' ? $href : null;
    }

    public function variant(): string
    {
        return (string) ($this->payload['variant'] ?? 'secondary');
    }

    public function type(): string
    {
        return (string) ($this->payload['type'] ?? 'link');
    }

    public function icon(): ?string
    {
        $icon = $this->payload['icon'] ?? null;

        return is_string($icon) && $icon !== '' ? $icon : null;
    }

    public function permission(): ?string
    {
        $permission = $this->payload['permission'] ?? null;

        return is_string($permission) && $permission !== '' ? $permission : null;
    }

    public function confirm(): ?string
    {
        $confirm = $this->payload['confirm'] ?? null;

        return is_string($confirm) && $confirm !== '' ? $confirm : null;
    }

    public function target(): ?string
    {
        $target = $this->payload['target'] ?? null;

        return is_string($target) && $target !== '' ? $target : null;
    }

    public function rel(): ?string
    {
        $rel = $this->payload['rel'] ?? null;

        return is_string($rel) && $rel !== '' ? $rel : null;
    }

    public function class(): ?string
    {
        $class = $this->payload['class'] ?? null;

        return is_string($class) && $class !== '' ? $class : null;
    }

    public function disabled(): bool
    {
        return (bool) ($this->payload['disabled'] ?? false);
    }

    public function title(): ?string
    {
        $title = $this->payload['title'] ?? null;

        return is_string($title) && $title !== '' ? $title : null;
    }

    public function action(): ?string
    {
        $action = $this->payload['action'] ?? null;

        return is_string($action) && $action !== '' ? $action : null;
    }

    public function method(): ?string
    {
        $method = $this->payload['method'] ?? null;

        return is_string($method) && $method !== '' ? $method : null;
    }

    public function button_type(): ?string
    {
        $buttonType = $this->payload['button_type'] ?? null;

        return is_string($buttonType) && $buttonType !== '' ? $buttonType : null;
    }

    public function onclick(): ?string
    {
        $onclick = $this->payload['onclick'] ?? null;

        return is_string($onclick) && $onclick !== '' ? $onclick : null;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->name(),
            'label' => $this->label(),
            'href' => $this->href(),
            'variant' => $this->variant(),
            'type' => $this->type(),
            'icon' => $this->icon(),
            'permission' => $this->permission(),
            'confirm' => $this->confirm(),
            'target' => $this->target(),
            'rel' => $this->rel(),
            'class' => $this->class(),
            'disabled' => $this->disabled(),
            'title' => $this->title(),
            'action' => $this->action(),
            'method' => $this->method(),
            'button_type' => $this->button_type(),
            'onclick' => $this->onclick(),
            default => $this->payload[$name] ?? null,
        };
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'name', 'label', 'variant', 'type', 'disabled' => true,
            'href', 'icon', 'permission', 'confirm', 'target', 'rel', 'class', 'title', 'action', 'method', 'button_type', 'onclick' => array_key_exists($name, $this->payload),
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
