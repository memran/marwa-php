<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->bigIncrements('id');
            $table->string('name', 120);
            $table->string('email', 190)->unique();
            $table->string('password', 255);
            $table->string('role', 32)->default('staff');
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
};
