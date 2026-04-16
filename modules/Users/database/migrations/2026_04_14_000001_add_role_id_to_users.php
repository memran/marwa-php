<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        // The starter now creates `role_id` directly in the base users migration.
    }

    public function down(): void
    {
        // No-op for backwards compatibility with cached installs.
    }
};
