<?php

declare(strict_types=1);

namespace App\Modules\DashboardStatus;

final class DashboardStatusCards
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function cards(): array
    {
        $basePath = base_path();
        $phpVersion = PHP_VERSION;
        $phpScore = $this->phpScore(PHP_VERSION_ID);
        $loadAverage = $this->loadAverage();
        $memoryLimit = $this->formatBytes($this->toBytes((string) ini_get('memory_limit')));
        $memoryScore = $this->memoryScore($this->toBytes((string) ini_get('memory_limit')));
        $diskFree = $this->formatBytes($this->safeDiskFreeSpace($basePath));
        $diskScore = $this->diskScore($this->safeDiskFreeSpace($basePath));
        $environment = (string) config('settings.lifecycle.app.env', config('app.env', 'production'));
        $debugMode = (bool) config('settings.lifecycle.app.debug', config('app.debug', false));
        $theme = (string) config('settings.lifecycle.theme.admin', config('view.adminTheme', 'admin'));
        $loadScore = $this->loadScore($loadAverage);
        $themeScore = $theme === 'admin' ? 90 : 78;

        return [
            $this->card(
                'Application',
                (string) config('settings.lifecycle.app.name', config('app.name', 'MarwaPHP')),
                sprintf('%s environment', ucfirst($environment)),
                $debugMode ? 'warning' : 'success',
                $debugMode ? 'Debug on' : 'Healthy',
                $debugMode ? 68 : 92,
                4
            ),
            $this->card(
                'Runtime',
                $phpVersion,
                php_sapi_name() ?: 'CLI',
                'primary',
                'Stable',
                $phpScore,
                4
            ),
            $this->card(
                'Memory limit',
                $memoryLimit,
                'Configured ceiling',
                'neutral',
                'Monitored',
                $memoryScore,
                4
            ),
            $this->card(
                'Disk free',
                $diskFree,
                'Available for logs and cache',
                'success',
                'Ready',
                $diskScore,
                4
            ),
            $this->card(
                'Load average',
                $loadAverage,
                '1 minute sample',
                'neutral',
                'Normal',
                $loadScore,
                3
            ),
            $this->card(
                'Admin theme',
                $theme,
                'Active workspace skin',
                'primary',
                'Loaded',
                $themeScore,
                4
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function card(
        string $label,
        string $value,
        string $meta,
        string $tone,
        string $status,
        int $metricPercent,
        int $barCount
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'meta' => $meta,
            'tone' => $tone,
            'status' => $status,
            'metric_percent' => $this->clampPercent($metricPercent),
            'metric_width' => $this->clampPercent($metricPercent) . '%',
            'metric_bars' => $this->buildBarWidths($metricPercent, $barCount),
        ];
    }

    private function phpScore(int $versionId): int
    {
        return match (true) {
            $versionId >= 80400 => 96,
            $versionId >= 80300 => 93,
            $versionId >= 80200 => 90,
            $versionId >= 80100 => 87,
            default => 78,
        };
    }

    private function memoryScore(int $bytes): int
    {
        return match (true) {
            $bytes <= 0 => 45,
            $bytes <= 134217728 => 40,
            $bytes <= 268435456 => 52,
            $bytes <= 536870912 => 66,
            $bytes <= 1073741824 => 78,
            default => 90,
        };
    }

    private function diskScore(int $bytes): int
    {
        return match (true) {
            $bytes <= 0 => 30,
            $bytes <= 1073741824 => 38,
            $bytes <= 5368709120 => 50,
            $bytes <= 21474836480 => 68,
            $bytes <= 107374182400 => 82,
            default => 94,
        };
    }

    private function loadScore(string $loadAverage): int
    {
        if (!is_numeric($loadAverage)) {
            return 64;
        }

        $value = (float) $loadAverage;

        return (int) max(18, min(96, 100 - ($value * 18)));
    }

    /**
     * @return array<int, string>
     */
    private function buildBarWidths(int $percent, int $count): array
    {
        $percent = $this->clampPercent($percent);
        $bars = [];

        for ($index = 0; $index < $count; $index++) {
            $bars[] = $this->clampPercent($percent - ($index * 14)) . '%';
        }

        return $bars;
    }

    private function clampPercent(int $percent): int
    {
        return max(18, min(100, $percent));
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
