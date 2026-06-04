<?php

declare(strict_types=1);

namespace App\Support;

final class AdminThemeResolver
{
    private const FALLBACK_THEME = 'admin';
    private const THEME_TYPE_ADMIN = 'admin';

    public function resolve(string $configuredTheme): string
    {
        $candidate = trim($configuredTheme);

        if ($candidate !== '' && $this->isAdminTheme($candidate)) {
            return $candidate;
        }

        if ($this->isAdminTheme(self::FALLBACK_THEME)) {
            return self::FALLBACK_THEME;
        }

        foreach ($this->availableThemes() as $themeName) {
            if ($this->isAdminTheme($themeName)) {
                return $themeName;
            }
        }

        return self::FALLBACK_THEME;
    }

    public function isAdminTheme(string $themeName): bool
    {
        $manifest = $this->readManifest($themeName);

        if ($manifest === null) {
            return false;
        }

        return strtolower(trim((string) ($manifest['type'] ?? ''))) === self::THEME_TYPE_ADMIN;
    }

    /**
     * @return list<string>
     */
    private function availableThemes(): array
    {
        $themesPath = $this->themesPath();

        if (!is_dir($themesPath)) {
            return [];
        }

        $themes = [];
        foreach (scandir($themesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($themesPath . DIRECTORY_SEPARATOR . $entry)) {
                $themes[] = $entry;
            }
        }

        sort($themes);

        return $themes;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifest(string $themeName): ?array
    {
        $themeDir = $this->themesPath() . DIRECTORY_SEPARATOR . $themeName;
        if (!is_dir($themeDir)) {
            return null;
        }

        $phpManifest = $themeDir . DIRECTORY_SEPARATOR . 'manifest.php';
        if (is_file($phpManifest)) {
            $manifest = require $phpManifest;

            return is_array($manifest) ? $manifest : null;
        }

        $jsonManifest = $themeDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!is_file($jsonManifest)) {
            return null;
        }

        try {
            $manifest = json_decode((string) file_get_contents($jsonManifest), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($manifest) ? $manifest : null;
    }

    private function themesPath(): string
    {
        try {
            $configuredPath = config('view.themePath');

            if (is_string($configuredPath) && trim($configuredPath) !== '') {
                return rtrim($configuredPath, DIRECTORY_SEPARATOR);
            }
        } catch (\Throwable) {
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'themes';
    }
}
