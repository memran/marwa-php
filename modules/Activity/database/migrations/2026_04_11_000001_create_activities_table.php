<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('activities', function ($table): void {
            $table->bigIncrements('id');
            $table->string('action', 80);
            $table->string('description', 255);
            $table->string('actor_name', 120)->nullable();
            $table->string('actor_email', 190)->nullable();
            $table->string('subject_type', 120)->nullable();
            $table->bigInteger('subject_id')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('activities');
    }
};
