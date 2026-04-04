<?php

declare(strict_types=1);

namespace App\Modules\Auth\Database\Seeders;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use Marwa\DB\Seeder\Seeder;
use Marwa\Framework\Supports\Config;
use Marwa\Support\Security;

final class AuthSeeder implements Seeder
{
    public function run(): void
    {
        /** @var Config $config */
        $config = app(Config::class);
        $config->loadIfExists('auth.php');

        $adminRole = $this->ensureRole(
            (string) $config->get('auth.defaults.admin_role', 'admin'),
            'Full access to the administration area.',
            false
        );
        $userRole = $this->ensureRole(
            (string) $config->get('auth.defaults.default_role', 'user'),
            'Default starter role.',
            true
        );

        $adminEmail = strtolower((string) $config->get('auth.seed.admin_email', 'admin@marwa.test'));
        $admin = User::query()->where('email', '=', $adminEmail)->first();

        if (!$admin instanceof User) {
            $admin = User::create([
                'name' => (string) $config->get('auth.seed.admin_name', 'Administrator'),
                'email' => $adminEmail,
                'password' => Security::hash((string) $config->get('auth.seed.admin_password', 'ChangeMe123!')),
                'status' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'remember_selector' => null,
                'remember_token_hash' => null,
                'remember_expires_at' => null,
                'last_login_at' => null,
            ]);
        }

        if ($adminRole instanceof Role || $userRole instanceof Role) {
            $roleIds = [];

            if ($adminRole instanceof Role) {
                $roleIds[] = (int) $adminRole->getKey();
            }

            if ($userRole instanceof Role) {
                $roleIds[] = (int) $userRole->getKey();
            }

            $roleIds = array_values(array_unique($roleIds));

            if ($roleIds !== []) {
                $admin->roles()->sync($admin, $roleIds);
            }
        }
    }

    private function ensureRole(string $slug, string $description, bool $default): ?Role
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $role = Role::query()->where('slug', '=', $slug)->first();

        if ($role instanceof Role) {
            return $role;
        }

        return Role::create([
            'name' => ucfirst(str_replace(['-', '_'], ' ', $slug)),
            'slug' => $slug,
            'description' => $description,
            'is_default' => $default,
        ]);
    }
}
