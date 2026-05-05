<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\DatabaseBackup\DatabaseBackupServiceProvider;
use League\Container\Container;
use Marwa\Framework\Navigation\MenuRegistry;
use PHPUnit\Framework\TestCase;

final class DatabaseBackupMenuTest extends TestCase
{
    public function test_database_backup_menu_item_is_registered_with_the_admin_tree(): void
    {
        $provider = new DatabaseBackupServiceProvider();
        $provider->setContainer(new Container());

        $menu = new MenuRegistry();
        $app = new class ($menu) {
            public function __construct(private readonly MenuRegistry $menu) {}

            public function has(string $id): bool
            {
                return $id === MenuRegistry::class;
            }

            public function make(string $id): mixed
            {
                return $id === MenuRegistry::class ? $this->menu : null;
            }
        };

        $menu->add([
            'name' => 'admin.system',
            'label' => 'System',
            'url' => '#',
            'order' => 30,
        ]);

        $provider->register($app);

        $items = $menu->all();
        $item = null;

        foreach ($items as $candidate) {
            if ($candidate['name'] === 'database-backup') {
                $item = $candidate;
                break;
            }
        }

        self::assertIsArray($item);
        self::assertSame('/admin/database-backups', $item['url']);
        self::assertSame('admin.system', $item['parent']);
    }
}
