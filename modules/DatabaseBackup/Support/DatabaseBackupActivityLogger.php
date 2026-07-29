<?php

declare(strict_types=1);

namespace App\Modules\DatabaseBackup\Support;

use App\Modules\Activity\Events\ActivityRecordingRequested;

final class DatabaseBackupActivityLogger
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function settingsUpdated(array $before, array $after): void
    {
        if ($before === $after) {
            return;
        }

        event(new ActivityRecordingRequested(
            'database_backup.settings_updated',
            'Updated database backup settings.',
            'database_backup',
            null,
            ['before' => $before, 'after' => $after]
        ));
    }

    /**
     * @param list<string> $tables
     */
    public function backupCreated(string $path, array $tables): void
    {
        event(new ActivityRecordingRequested(
            'database_backup.created',
            'Created a database backup.',
            'database_backup',
            null,
            ['state' => ['path' => $path, 'table_count' => count($tables)]]
        ));
    }

    /**
     * @param list<string> $tables
     */
    public function databaseRestored(string $filename, array $tables): void
    {
        event(new ActivityRecordingRequested(
            'database_backup.restored',
            'Restored the database from a backup.',
            'database_backup',
            null,
            ['state' => ['filename' => $filename, 'table_count' => count($tables)]]
        ));
    }
}
