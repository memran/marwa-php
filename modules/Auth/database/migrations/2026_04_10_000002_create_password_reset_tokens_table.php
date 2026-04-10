<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('password_reset_tokens', function ($table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('password_reset_tokens');
    }
};
