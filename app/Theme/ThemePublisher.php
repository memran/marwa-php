<?php

declare(strict_types=1);

namespace App\Theme;

use App\Modules\Settings\Support\SettingsStore;
use InvalidArgumentException;
use RuntimeException;

final class ThemePublisher
{
    public function __construct(
        private readonly ThemeValidator $validator,
        private readonly ?AdminThemePersistence $persistence = null
    ) {
    }

    public function publish(string $themeName): ThemePublishResult
    {
        $themeName = $this->normalizeThemeName($themeName);
        $validation = $this->validator->validate($themeName);

        if (!$validation->isValid()) {
            throw new RuntimeException(sprintf('Theme "%s" is invalid and cannot be published.', $themeName));
        }

        $manifest = $validation->manifest();
        $themeType = strtolower(trim((string) ($manifest['type'] ?? '')));

        if ($themeType !== 'admin') {
            throw new RuntimeException(sprintf('Theme "%s" is not an admin theme.', $themeName));
        }

        $this->persistence()->publish($themeName);

        return new ThemePublishResult(
            $themeName,
            'database'
        );
    }

    private function normalizeThemeName(string $themeName): string
    {
        $themeName = strtolower(trim($themeName));

        if ($themeName === '') {
            throw new InvalidArgumentException('Theme name cannot be empty.');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $themeName) !== 1) {
            throw new InvalidArgumentException('Theme name must use lowercase letters, numbers, and hyphens only.');
        }

        return $themeName;
    }

    private function persistence(): AdminThemePersistence
    {
        if ($this->persistence instanceof AdminThemePersistence) {
            return $this->persistence;
        }

        try {
            $store = app(SettingsStore::class);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Settings store is not available. Publish the theme after the Settings module is booted.',
                previous: $exception
            );
        }

        if (!$store instanceof SettingsStore) {
            throw new RuntimeException('Settings store is not available. Publish the theme after the Settings module is booted.');
        }

        return new SettingsAdminThemePersistence($store);
    }
}
