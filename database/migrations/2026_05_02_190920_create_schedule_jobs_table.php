<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('schedule_jobs', function ($table): void {
            $table->increments('id');
            $table->string('name', 191)->unique();
            $table->string('description', 255)->nullable();
            $table->string('status', 50)->default('idle');
            $table->text('last_message')->nullable();
            $table->dateTime('lock_expires_at')->nullable();
            $table->dateTime('last_ran_at')->nullable();
            $table->dateTime('last_finished_at')->nullable();
            $table->dateTime('last_failed_at')->nullable();
            $table->dateTime('last_skipped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('schedule_jobs');
    }
};
