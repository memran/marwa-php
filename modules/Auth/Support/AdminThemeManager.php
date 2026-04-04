<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use Marwa\Framework\Contracts\SessionInterface;

final class AdminThemeManager
{
    private const SESSION_KEY = 'auth_admin_theme';
    private const LIGHT = 'light';
    private const DARK = 'dark';

    public function __construct(
        private SessionInterface $session
    ) {}

    public function current(): string
    {
        $theme = $this->session->get(self::SESSION_KEY, self::LIGHT);

        if (!is_string($theme)) {
            return self::LIGHT;
        }

        return $theme === self::DARK ? self::DARK : self::LIGHT;
    }

    public function isDark(): bool
    {
        return $this->current() === self::DARK;
    }

    public function toggle(): string
    {
        $theme = $this->isDark() ? self::LIGHT : self::DARK;

        $this->session->set(self::SESSION_KEY, $theme);

        return $theme;
    }

    public function label(): string
    {
        return $this->isDark() ? 'Light mode' : 'Dark mode';
    }
}
