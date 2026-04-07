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

    public function current(): string
    {
        $this->ensureSession();

        $theme = $_SESSION['theme_name'] ?? null;

        return $this->resolve(is_string($theme) ? $theme : $this->defaultTheme());
    }

    public function resolve(?string $themeName): string
    {
        if ($themeName === null) {
            return $this->defaultTheme();
        }

        return in_array($themeName, $this->availableThemes(), true) ? $themeName : $this->defaultTheme();
    }

    public function persist(string $themeName): void
    {
        $this->ensureSession();
        $_SESSION['theme_name'] = $this->resolve($themeName);
    }

    public function applyToView(?string $themeName = null): void
    {
        $builder = app(ViewAdapter::class)->getView()->getThemeBuilder();

        if ($builder === null) {
            return;
        }

        $builder->useTheme($this->resolve($themeName ?? $this->current()));
    }

    /**
     * @return list<string>
     */
    public function availableThemes(): array
    {
        $themes = $this->themes;
        $themes[] = $this->defaultTheme();
        $themes[] = $this->adminTheme();

        return array_values(array_unique(array_filter($themes, static fn (string $theme): bool => $theme !== '')));
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        session_start();
    }

    private function defaultTheme(): string
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

    private function adminTheme(): string
    {
        try {
            $theme = config('view.adminTheme', null);
            if (is_string($theme) && trim($theme) !== '') {
                return trim($theme);
            }
        } catch (\RuntimeException) {
            // No application container during isolated unit tests.
        }

        $theme = env('ADMIN_THEME', $this->defaultTheme());

        return is_string($theme) && trim($theme) !== '' ? trim($theme) : $this->defaultTheme();
    }
}
