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

        $widgets = [
            ['app_status', 'Application Status', 0, 'medium'],
            ['runtime_info', 'Runtime Info', 1, 'small'],
            ['memory_usage', 'Memory Usage', 2, 'small'],
            ['disk_space', 'Disk Space', 3, 'small'],
            ['load_average', 'Load Average', 4, 'small'],
            ['theme_info', 'Theme Info', 5, 'small'],
        ];

        $timestamp = gmdate('Y-m-d H:i:s');
        $pdo = db()->getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO dashboard_widgets (widget_id, widget_type, title, position, width, enabled, config, created_at, updated_at) 
             VALUES (:widget_id, :widget_type, :title, :position, :width, :enabled, :config, :created_at, :updated_at)"
        );

        foreach ($widgets as $widget) {
            $stmt->execute([
                'widget_id' => $widget[0],
                'widget_type' => 'system',
                'title' => $widget[1],
                'position' => $widget[2],
                'width' => $widget[3],
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
};