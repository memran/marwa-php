<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use App\Modules\Activity\Events\ActivityRecordingRequested;

final class SettingsActivityLogger
{
    /**
     * @param array<string, array<string, mixed>> $before
     * @param array<string, array<string, mixed>> $after
     */
    public function settingsUpdated(array $before, array $after): void
    {
        if ($before === $after) {
            return;
        }

        event(new ActivityRecordingRequested(
            'settings.updated',
            'Updated settings.',
            'settings',
            null,
            ['before' => $before, 'after' => $after]
        ));
    }

    public function cacheCleared(): void
    {
        event(new ActivityRecordingRequested(
            'settings.cache_cleared',
            'Cleared settings cache.',
            'settings',
            null,
            ['state' => ['cache' => 'flushed']]
        ));
    }

    public function logsCleared(int $deletedFiles): void
    {
        event(new ActivityRecordingRequested(
            'settings.logs_cleared',
            'Cleared log files.',
            'settings',
            null,
            ['state' => ['deleted_files' => $deletedFiles]]
        ));
    }
}
