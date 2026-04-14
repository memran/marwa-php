<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('permissions', function ($table): void {
            $table->bigIncrements('id');
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('group', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('permissions');
    }
};