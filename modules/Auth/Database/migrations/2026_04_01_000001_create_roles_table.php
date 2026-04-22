<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('roles', function ($table): void {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->integer('level')->default(1);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('roles');
    }
};