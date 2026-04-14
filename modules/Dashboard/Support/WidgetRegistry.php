<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Support;

final class WidgetRegistry
{
    private array $widgets = [];

    public function __construct()
    {
        $this->registerSystemWidgets();
    }

    private function registerSystemWidgets(): void
    {
        $widgets = [
            'app_status' => [
                'id' => 'app_status',
                'name' => 'Application Status',
                'description' => 'Shows application name and environment status',
                'size' => 'medium',
                'default' => true,
                'refreshable' => true,
            ],
            'runtime_info' => [
                'id' => 'runtime_info',
                'name' => 'Runtime Info',
                'description' => 'PHP version and server API information',
                'size' => 'small',
                'default' => true,
                'refreshable' => true,
            ],
            'memory_usage' => [
                'id' => 'memory_usage',
                'name' => 'Memory Usage',
                'description' => 'Current PHP memory limit',
                'size' => 'small',
                'default' => true,
                'refreshable' => true,
            ],
            'disk_space' => [
                'id' => 'disk_space',
                'name' => 'Disk Space',
                'description' => 'Available disk space for storage',
                'size' => 'small',
                'default' => true,
                'refreshable' => true,
            ],
            'load_average' => [
                'id' => 'load_average',
                'name' => 'Load Average',
                'description' => 'Server load average',
                'size' => 'small',
                'default' => true,
                'refreshable' => true,
            ],
            'theme_info' => [
                'id' => 'theme_info',
                'name' => 'Theme Info',
                'description' => 'Current admin theme',
                'size' => 'small',
                'default' => true,
                'refreshable' => true,
            ],
        ];

        foreach ($widgets as $widget) {
            $this->register($widget);
        }
    }

    public function register(array $widget): void
    {
        $this->widgets[$widget['id']] = $widget;
    }

    public function get(string $id): ?array
    {
        return $this->widgets[$id] ?? null;
    }

    public function all(): array
    {
        return $this->widgets;
    }

    public function getDefaults(): array
    {
        return array_filter($this->widgets, fn($w) => $w['default'] ?? false);
    }

    public function getSizeOptions(): array
    {
        return [
            'small' => 'Small (1 column)',
            'medium' => 'Medium (2 columns)',
            'large' => 'Large (full width)',
        ];
    }
}