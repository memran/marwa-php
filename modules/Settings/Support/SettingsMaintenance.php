<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Support\File;

final class SettingsMaintenance
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function purgeCache(): void
    {
        $this->cache->flush();
    }

    public function clearLogs(): int
    {
        $logsPath = logs_path();

        if (!is_dir($logsPath)) {
            return 0;
        }

        $count = 0;

        foreach (glob($logsPath . DIRECTORY_SEPARATOR . '*.log') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }

            File::delete($file);
            $count++;
        }

        return $count;
    }
}
