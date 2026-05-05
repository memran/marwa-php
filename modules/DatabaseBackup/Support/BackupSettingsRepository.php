<?php

declare(strict_types=1);

namespace App\Modules\DatabaseBackup\Support;

use Marwa\Framework\Supports\File;

final class BackupSettingsRepository
{
    private const FILE = 'database-backups/settings.json';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $path = $this->path();

        if (!is_file($path)) {
            return $defaults;
        }

        try {
            $stored = File::path($path)->readJson();
        } catch (\Throwable) {
            return $defaults;
        }

        return array_replace_recursive($defaults, is_array($stored) ? $stored : []);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function save(array $values): void
    {
        File::path($this->path())->writeJson(array_replace_recursive($this->defaults(), $values));
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $disks = config('storage.disks', []);
        $diskNames = is_array($disks) ? array_keys($disks) : [];

        return [
            'enabled' => true,
            'mode' => 'daily_at',
            'time' => '02:00',
            'day_of_week' => 1,
            'day_of_month' => 1,
            'interval_minutes' => 1440,
            'storage_disk' => in_array('local', $diskNames, true) ? 'local' : (string) (array_key_first($disks) ?? 'local'),
            'storage_path' => 'database-backups',
            'archive_format' => 'zip',
            'scope' => 'full',
            'tables' => [],
        ];
    }

    public function path(): string
    {
        return storage_path('app/' . self::FILE);
    }
}
