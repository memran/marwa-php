<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Schema\Builder;

return new class extends AbstractMigration {
    public function up(): void
    {
        Builder::useConnectionManager(app(ConnectionManager::class))->table('users', function ($table): void {
            $table->string('two_factor_secret', 255)->nullable();
            $table->dateTime('two_factor_enabled_at')->nullable();
        });
    }

    public function down(): void
    {
    }
};