<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Support;

use Marwa\Module\Contracts\ModuleRegistryInterface;

final class WidgetRegistry
{
    private array $widgets = [];

    public function __construct(private ?ModuleRegistryInterface $moduleRegistry = null)
    {
        $this->loadModuleWidgets();
    }

    private function loadModuleWidgets(): void
    {
        $this->moduleRegistry ??= $this->resolveModuleRegistry();

        if (!$this->moduleRegistry) {
            return;
        }

        foreach ($this->moduleRegistry->all() as $module) {
            $manifest = $module->manifest();
            $widgets = $manifest['widgets'] ?? $this->loadWidgetsFromManifestFile($module);

            if (!is_array($widgets)) {
                continue;
            }

            foreach ($widgets as $id => $widget) {
                if (!is_array($widget)) {
                    continue;
                }

                $normalized = $this->normalizeWidget($id, $widget, $module->slug());
                if ($normalized !== null) {
                    $this->register($normalized);
                }
            }
        }
    }

    /**
     * @return array<string|int, array<string, mixed>>
     */
    private function loadWidgetsFromManifestFile(\Marwa\Module\Module $module): array
    {
        $manifestFile = $module->basePath() . DIRECTORY_SEPARATOR . 'manifest.php';

        if (!is_file($manifestFile)) {
            return [];
        }

        /** @var mixed $manifest */
        $manifest = require $manifestFile;

        if (!is_array($manifest)) {
            return [];
        }

        $widgets = $manifest['widgets'] ?? [];

        return is_array($widgets) ? $widgets : [];
    }

    private function resolveModuleRegistry(): ?ModuleRegistryInterface
    {
        try {
            /** @var ModuleRegistryInterface $moduleRegistry */
            $moduleRegistry = app(ModuleRegistryInterface::class);

            return $moduleRegistry;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $widget
     * @return array<string, mixed>|null
     */
    private function normalizeWidget(string|int $key, array $widget, string $moduleSlug): ?array
    {
        $id = $widget['id'] ?? $key;
        if (!is_string($id) || trim($id) === '') {
            return null;
        }

        $name = $widget['name'] ?? $id;
        $description = $widget['description'] ?? '';
        $size = $widget['size'] ?? 'small';
        $view = $widget['view'] ?? 'widgets/' . $id;
        $card = $widget['card'] ?? null;

        return [
            'id' => $id,
            'name' => is_string($name) ? $name : $id,
            'description' => is_string($description) ? $description : '',
            'size' => is_string($size) ? $size : 'small',
            'default' => (bool) ($widget['default'] ?? false),
            'refreshable' => (bool) ($widget['refreshable'] ?? true),
            'module' => $moduleSlug,
            'namespace' => $widget['namespace'] ?? $this->moduleNamespace($moduleSlug),
            'view' => is_string($view) && trim($view) !== '' ? $view : 'widgets/' . $id,
            'card' => is_array($card) ? $card : [],
        ];
    }

    private function moduleNamespace(string $slug): string
    {
        $namespace = preg_replace('/[^A-Za-z0-9_]/', '_', $slug) ?: 'Module';

        if (preg_match('/^[A-Za-z]/', $namespace) !== 1) {
            $namespace = 'Module_' . $namespace;
        }

        return $namespace;
    }

    public function register(array $widget): void
    {
        $this->widgets[$widget['id']] = $widget;
    }

    public function get(string $id): ?array
    {
        $this->loadModuleWidgets();

        return $this->widgets[$id] ?? null;
    }

    public function all(): array
    {
        $this->loadModuleWidgets();

        return $this->widgets;
    }

    public function getDefaults(): array
    {
        $this->loadModuleWidgets();

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
