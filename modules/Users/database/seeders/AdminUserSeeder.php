<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use Marwa\DB\Seeder\Seeder;

final class AdminUserSeeder implements Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', 'admin@marwa.test'));
        $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', 'ExampleAdminPassword123!');

        $adminRole = Role::findBy('slug', 'admin');

        if ($adminRole === null) {
            $adminRole = Role::create([
                'name' => 'Admin',
                'slug' => 'admin',
                'level' => 10,
                'description' => 'Administrative access',
                'is_system' => 1,
            ]);
        }

        User::create([
            'name' => 'Administrator',
            'email' => $email !== '' ? $email : 'admin@marwa.test',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => (int) $adminRole->getKey(),
            'is_active' => true,
        ]);
    }
}
