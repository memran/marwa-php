<?php

declare(strict_types=1);

namespace App\Modules\DashboardStatus;

final class DashboardStatusCards
{
    /**
     * @return array<int, array<string, string>>
     */
    public function cards(): array
    {
        $basePath = base_path();
        $phpVersion = PHP_VERSION;
        $loadAverage = $this->loadAverage();
        $memoryLimit = $this->formatBytes($this->toBytes((string) ini_get('memory_limit')));
        $diskFree = $this->formatBytes($this->safeDiskFreeSpace($basePath));
        $environment = (string) env('APP_ENV', 'production');
        $debugMode = (bool) env('APP_DEBUG', false);
        $theme = (string) config('view.adminTheme', 'admin');

        return [
            [
                'label' => 'Application',
                'value' => (string) config('app.name', 'MarwaPHP'),
                'meta' => sprintf('%s environment', ucfirst($environment)),
                'tone' => $debugMode ? 'warning' : 'success',
                'status' => $debugMode ? 'Debug on' : 'Healthy',
            ],
            [
                'label' => 'Runtime',
                'value' => $phpVersion,
                'meta' => php_sapi_name() ?: 'CLI',
                'tone' => 'primary',
                'status' => 'Stable',
            ],
            [
                'label' => 'Memory limit',
                'value' => $memoryLimit,
                'meta' => 'Configured ceiling',
                'tone' => 'neutral',
                'status' => 'Monitored',
            ],
            [
                'label' => 'Disk free',
                'value' => $diskFree,
                'meta' => 'Available for logs and cache',
                'tone' => 'success',
                'status' => 'Ready',
            ],
            [
                'label' => 'Load average',
                'value' => $loadAverage,
                'meta' => '1 minute sample',
                'tone' => 'neutral',
                'status' => 'Normal',
            ],
            [
                'label' => 'Admin theme',
                'value' => $theme,
                'meta' => 'Active workspace skin',
                'tone' => 'primary',
                'status' => 'Loaded',
            ],
        ];
    }

    private function loadAverage(): string
    {
        if (!function_exists('sys_getloadavg')) {
            return 'n/a';
        }

        $load = sys_getloadavg();

        if (!is_array($load) || !isset($load[0])) {
            return 'n/a';
        }

        return number_format((float) $load[0], 2);
    }

    private function safeDiskFreeSpace(string $path): int
    {
        $free = @disk_free_space($path);

        return $free === false ? 0 : (int) $free;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return 'n/a';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $index = 0;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1) . ' ' . $units[$index];
    }

    private function toBytes(string $value): int
    {
        $value = trim(strtolower($value));

        if ($value === '' || $value === '-1') {
            return 0;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)([kmgtp]?b)?$/', $value, $matches) !== 1) {
            return (int) $value;
        }

        $number = (float) $matches[1];
        $unit = $matches[2] ?? 'b';

        return match ($unit) {
            'kb' => (int) ($number * 1024),
            'mb' => (int) ($number * 1024 * 1024),
            'gb' => (int) ($number * 1024 * 1024 * 1024),
            'tb' => (int) ($number * 1024 * 1024 * 1024 * 1024),
            default => (int) $number,
        };
    }
}
