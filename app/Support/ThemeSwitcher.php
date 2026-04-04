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

        $theme = $_SESSION['theme_name'] ?? 'default';

        return $this->resolve(is_string($theme) ? $theme : 'default');
    }

    public function resolve(?string $themeName): string
    {
        if ($themeName === null) {
            return 'default';
        }

        return in_array($themeName, $this->themes, true) ? $themeName : 'default';
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
        return $this->themes;
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
}
