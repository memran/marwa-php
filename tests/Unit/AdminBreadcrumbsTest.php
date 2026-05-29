<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AdminBreadcrumbs;
use PHPUnit\Framework\TestCase;

final class AdminBreadcrumbsTest extends TestCase
{
    public function testItBuildsUsersCreateBreadcrumbsFromPath(): void
    {
        $crumbs = AdminBreadcrumbs::fromRequestPath('/admin/users/create');

        self::assertSame([
            ['label' => 'Dashboard', 'url' => '/admin', 'active' => false],
            ['label' => 'Users', 'url' => '/admin/users', 'active' => false],
            ['label' => 'Create user', 'url' => null, 'active' => true],
        ], $crumbs);
    }

    public function testItCollapsesModuleAndPageWhenTheyMatch(): void
    {
        $crumbs = AdminBreadcrumbs::fromRequestPath('/admin/settings');

        self::assertSame([
            ['label' => 'Dashboard', 'url' => '/admin', 'active' => false],
            ['label' => 'Settings', 'url' => '/admin/settings', 'active' => true],
        ], $crumbs);
    }
}
