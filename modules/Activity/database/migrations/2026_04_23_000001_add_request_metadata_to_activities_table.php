<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Builder;

return new class extends AbstractMigration {
    public function up(): void
    {
        (new Builder(db()))->table('activities', function ($table): void {
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });
    }

    public function down(): void
    {
    }
};
