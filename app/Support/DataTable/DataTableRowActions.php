<?php

declare(strict_types=1);

namespace App\Support\DataTable;

final class DataTableRowActions
{
    /**
     * @param list<array<string, mixed>> $actions
     * @return list<array<string, mixed>>
     */
    public function build(array $actions): array
    {
        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    public function link(string $label, string $href, string $variant = 'secondary', ?string $permission = null): array
    {
        $action = [
            'type' => 'link',
            'label' => $label,
            'href' => $href,
            'variant' => $variant,
        ];
        if ($permission !== null) {
            $action['permission'] = $permission;
        }
        return $action;
    }

    /**
     * @return array<string, mixed>
     */
    public function formButton(
        string $label,
        string $action,
        string $variant = 'secondary',
        ?string $icon = null,
        ?string $permission = null,
        ?string $confirm = null
    ): array {
        $btn = [
            'type' => 'form_button',
            'label' => $label,
            'action' => $action,
            'variant' => $variant,
        ];
        if ($icon !== null) {
            $btn['icon'] = $icon;
        }
        if ($permission !== null) {
            $btn['permission'] = $permission;
        }
        if ($confirm !== null) {
            $btn['confirm'] = $confirm;
        }
        return $btn;
    }

    /**
     * @return array<string, mixed>
     */
    public function disabledButton(string $label, string $title, string $variant = 'danger', ?string $icon = null): array
    {
        $btn = [
            'type' => 'button',
            'label' => $label,
            'variant' => $variant,
            'disabled' => true,
            'title' => $title,
        ];
        if ($icon !== null) {
            $btn['icon'] = $icon;
        }
        return $btn;
    }
}
