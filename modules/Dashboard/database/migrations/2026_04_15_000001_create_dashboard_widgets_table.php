<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('dashboard_widgets', function ($table): void {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable()->unsigned();
            $table->string('widget_id', 100);
            $table->string('widget_type', 50)->default('system');
            $table->string('title', 255);
            $table->integer('position')->default(0);
            $table->string('width', 20)->default('medium');
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'widget_id'], 'dashboard_user_widget_unique');
            $table->index('user_id', 'dashboard_user_id_idx');
        });

        $timestamp = gmdate('Y-m-d H:i:s');
        $pdo = db()->getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO dashboard_widgets (widget_id, widget_type, title, position, width, enabled, config, created_at, updated_at) 
             VALUES (:widget_id, :widget_type, :title, :position, :width, :enabled, :config, :created_at, :updated_at)"
        );

        foreach ($this->defaultWidgets() as $index => $widget) {
            $stmt->execute([
                'widget_id' => $widget['id'],
                'widget_type' => 'system',
                'title' => $widget['name'],
                'position' => $index,
                'width' => $widget['size'],
                'enabled' => 1,
                'config' => '{}',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        Schema::drop('dashboard_widgets');
    }

    /**
     * @return array<int, array{id:string,name:string,size:string}>
     */
    private function defaultWidgets(): array
    {
        $manifestFile = base_path('modules/DashboardStatus/manifest.php');

        if (!is_file($manifestFile)) {
            return [];
        }

        /** @var mixed $manifest */
        $manifest = require $manifestFile;

        if (!is_array($manifest) || !is_array($manifest['widgets'] ?? null)) {
            return [];
        }

        $widgets = [];

        foreach ($manifest['widgets'] as $id => $widget) {
            if (!is_array($widget)) {
                continue;
            }

            if (!($widget['default'] ?? false)) {
                continue;
            }

            $widgetId = is_string($id) && trim($id) !== '' ? trim($id) : null;
            $name = $widget['name'] ?? $widgetId;
            $size = $widget['size'] ?? 'small';

            if ($widgetId === null || !is_string($name) || !is_string($size)) {
                continue;
            }

            $widgets[] = [
                'id' => $widgetId,
                'name' => $name,
                'size' => $size,
            ];
        }

        return $widgets;
    }
};
