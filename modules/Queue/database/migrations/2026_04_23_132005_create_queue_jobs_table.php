<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('queue_jobs', function ($table): void {
            $table->string('id', 32)->primary();
            $table->string('name', 255);
            $table->string('queue', 64)->default('default');
            $table->text('payload');
            $table->integer('attempts')->default(0);
            $table->integer('available_at');
            $table->integer('reserved_at')->nullable();
            $table->string('reserved_by', 64)->nullable();
            $table->integer('completed_at')->nullable();
            $table->integer('failed_at')->nullable();
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index(['queue', 'available_at', 'reserved_at', 'completed_at', 'failed_at'], 'queue_status');
            $table->index(['reserved_at', 'reserved_by'], 'reserved');
            $table->index('name');
            $table->index('queue');
            $table->index('available_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::drop('queue_jobs');
    }
};
