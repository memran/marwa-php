<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\PermissionAwareUser;
use App\Support\PermissionGate;
use PHPUnit\Framework\TestCase;

final class PermissionGateTest extends TestCase
{
    public function testGateDeniesWhenNoUserIsResolved(): void
    {
        $gate = new PermissionGate();

        self::assertFalse($gate->allows('dashboard.view'));
        self::assertTrue($gate->denies('dashboard.view'));
    }

    public function testGateUsesTheResolvedUserPermissions(): void
    {
        $user = new class implements PermissionAwareUser {
            /**
             * @var list<string>
             */
            private array $permissions = ['dashboard.view'];

            public function hasPermission(string $permission): bool
            {
                return in_array($permission, $this->permissions, true);
            }
        };

        $gate = (new PermissionGate())->withCurrentUserResolver(
            static fn (): PermissionAwareUser => $user
        );

        self::assertTrue($gate->allows('dashboard.view'));
        self::assertFalse($gate->denies('dashboard.view'));
        self::assertFalse($gate->allows('users.edit'));
        self::assertTrue($gate->denies('users.edit'));
    }
}
