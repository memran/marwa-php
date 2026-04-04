<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('auth_role_user', function ($table): void {
            $table->bigIncrements('id');
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
            $table->foreign('user_id', 'auth_users', 'id');
            $table->foreign('role_id', 'auth_roles', 'id');
        });
    }

    public function down(): void
    {
        Schema::drop('auth_role_user');
    }
};
