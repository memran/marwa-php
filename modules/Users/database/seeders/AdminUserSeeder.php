<?php

declare(strict_types=1);

namespace App\Modules\Users\Database\Seeders;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use Marwa\DB\Seeder\Seeder;

final class AdminUserSeeder implements Seeder
{
    public function run(): void
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return;
        }

        if (User::query()->count() > 0) {
            return;
        }

        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', 'admin@marwa.test'));
        $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', 'ExampleAdminPassword123!');

        $adminRole = $this->ensureRole('Admin', 'admin', 5, 'Administrative access', 1);
        $this->ensureRole('Manager', 'manager', 4, 'Team management', 0);
        $this->ensureRole('Staff', 'staff', 2, 'Operational access', 0);

        $roleId = (int) $adminRole->getKey();

        User::create([
            'name' => 'Administrator',
            'email' => $email !== '' ? $email : 'admin@marwa.test',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $roleId,
            'is_active' => true,
        ]);
    }

    private function ensureRole(string $name, string $slug, int $level, string $description, int $isSystem): Role
    {
        $role = Role::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug)
            ->first();

        if ($role !== null) {
            return Role::newInstance(is_array($role) ? $role : (array) $role, true);
        }

        return Role::create([
            'name' => $name,
            'slug' => $slug,
            'level' => $level,
            'description' => $description,
            'is_system' => $isSystem,
        ]);
    }
}
