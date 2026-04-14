<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('role');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        Schema::table('users', function ($table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->string('role', 32)->default('staff')->after('password');
        });

        Schema::table('users', function ($table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
