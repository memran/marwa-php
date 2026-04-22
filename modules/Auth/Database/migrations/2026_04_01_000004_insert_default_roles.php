<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Facades\DB;

return new class extends AbstractMigration {
    public function up(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'level' => 10, 'description' => 'Administrative access with full permissions', 'is_system' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'User', 'slug' => 'user', 'level' => 1, 'description' => 'Regular user access with limited permissions', 'is_system' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role);
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('slug', ['admin', 'user'])->delete();
    }
};
