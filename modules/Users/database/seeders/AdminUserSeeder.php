<?php

declare(strict_types=1);

namespace Database\Seeders;

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

        User::create([
            'name' => 'Administrator',
            'email' => $email !== '' ? $email : 'admin@marwa.test',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
