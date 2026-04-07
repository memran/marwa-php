<?php

declare(strict_types=1);

namespace App\Support;

use Marwa\Framework\Adapters\ViewAdapter;

final class ThemeSwitcher
{
    /**
     * @var list<string>
     */
    private array $themes = ['default', 'dark'];

    public function resolve(?string $themeName): string
    {
        if ($themeName === null) {
            return $this->frontendTheme();
        }

        return in_array($themeName, $this->availableThemes(), true) ? $themeName : $this->frontendTheme();
    }

    public function themeForRequest(string $fallbackTheme, ?string $themeName = null, mixed $preview = null): string
    {
        $fallbackTheme = $this->resolve($fallbackTheme);
        $previewEnabled = $this->isPreviewRequested($preview);
        $themeName = $themeName !== null ? trim($themeName) : null;

        if (!$previewEnabled) {
            return $fallbackTheme;
        }

        return $this->resolve($themeName ?? $fallbackTheme);
    }

    public function applyToView(string $fallbackTheme, ?string $themeName = null, mixed $preview = null): string
    {
        $builder = app(ViewAdapter::class)->getView()->getThemeBuilder();
        $theme = $this->themeForRequest($fallbackTheme, $themeName, $preview);

        if ($builder === null) {
            return $theme;
        }

        if ($this->isPreviewRequested($preview) && $theme !== $fallbackTheme) {
            $builder->previewTheme($theme);
        } else {
            $builder->useTheme($theme);
        }

        return $theme;
    }

    /**
     * @return list<string>
     */
    public function availableThemes(): array
    {
        $themes = $this->themes;
        $themes[] = $this->frontendTheme();
        $themes[] = $this->adminTheme();

        return array_values(array_unique(array_filter($themes, static fn (string $theme): bool => $theme !== '')));
    }

    public function frontendTheme(): string
    {
        try {
            $theme = config('view.frontendTheme', null);
            if (is_string($theme) && trim($theme) !== '') {
                return trim($theme);
            }
        } catch (\RuntimeException) {
            // No application container during isolated unit tests.
        }

        $theme = env('FRONTEND_THEME', 'default');

        return is_string($theme) && trim($theme) !== '' ? trim($theme) : 'default';
    }

    public function adminTheme(): string
    {
        try {
            $theme = config('view.adminTheme', null);
            if (is_string($theme) && trim($theme) !== '') {
                return trim($theme);
            }
        } catch (\RuntimeException) {
            // No application container during isolated unit tests.
        }

        $theme = env('ADMIN_THEME', 'admin');

        return is_string($theme) && trim($theme) !== '' ? trim($theme) : 'admin';
    }

    private function isPreviewRequested(mixed $preview): bool
    {
        if (is_bool($preview)) {
            return $preview;
        }

        if (is_int($preview) || is_float($preview)) {
            return (bool) $preview;
        }

        if (!is_string($preview)) {
            return false;
        }

        $preview = trim($preview);

        if ($preview === '') {
            return false;
        }

        return !in_array(strtolower($preview), ['0', 'false', 'off', 'no'], true);
    }
}
