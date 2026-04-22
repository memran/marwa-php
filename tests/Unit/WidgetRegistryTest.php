<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Dashboard\Support\WidgetRegistry;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\Module;
use PHPUnit\Framework\TestCase;

final class WidgetRegistryTest extends TestCase
{
    public function test_it_loads_widget_definitions_from_module_manifests(): void
    {
        $moduleRegistry = new class implements ModuleRegistryInterface {
            public function all(): array
            {
                return [
                    'dashboard-status' => new Module('dashboard-status', 'modules/DashboardStatus', [
                        'name' => 'Dashboard Status',
                        'slug' => 'dashboard-status',
                        'widgets' => [
                            'app_status' => [
                                'name' => 'Application Status',
                                'description' => 'Shows application name and environment status',
                                'size' => 'medium',
                                'default' => true,
                                'refreshable' => true,
                            ],
                        ],
                    ]),
                ];
            }

            public function has(string $slug): bool
            {
                return $slug === 'dashboard-status';
            }

            public function get(string $slug): ?Module
            {
                return $slug === 'dashboard-status' ? $this->all()['dashboard-status'] : null;
            }

            public function findByPath(string $path): ?Module
            {
                return null;
            }

            public function reload(): void
            {
            }
        };

        $registry = new WidgetRegistry($moduleRegistry);
        $widget = $registry->get('app_status');

        self::assertIsArray($widget);
        self::assertSame('dashboard-status', $widget['module']);
        self::assertSame('dashboard_status', $widget['namespace']);
        self::assertSame('widgets/app_status', $widget['view']);
        self::assertSame('Application Status', $widget['name']);
    }

    public function test_it_is_empty_when_no_module_widgets_exist(): void
    {
        $registry = new WidgetRegistry();

        self::assertSame([], $registry->all());
        self::assertNull($registry->get('app_status'));
    }
}
