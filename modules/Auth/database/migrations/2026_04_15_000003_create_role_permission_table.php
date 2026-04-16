<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('role_permission', function ($table): void {
            $table->bigInteger('role_id')->unsigned();
            $table->bigInteger('permission_id')->unsigned();
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id', 'roles', 'id', null, ['onDelete' => 'cascade']);
            $table->foreign('permission_id', 'permissions', 'id', null, ['onDelete' => 'cascade']);
        });
    }

    public function down(): void
    {
        Schema::drop('role_permission');
    }
};
