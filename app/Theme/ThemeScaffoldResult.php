<?php

declare(strict_types=1);

namespace App\Theme;

final readonly class ThemeScaffoldResult
{
    public function __construct(
        private string $themeName,
        private string $themePath,
        private string $publicThemePath
    ) {
    }

    public function themeName(): string
    {
        return $this->themeName;
    }

    public function themePath(): string
    {
        return $this->themePath;
    }

    public function publicThemePath(): string
    {
        return $this->publicThemePath;
    }
}
