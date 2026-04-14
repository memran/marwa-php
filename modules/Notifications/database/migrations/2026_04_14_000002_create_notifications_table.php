<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('notifications', function ($table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 20)->default('info');
            $table->string('title', 150);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('action_url', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::drop('notifications');
    }
};