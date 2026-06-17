<?php

declare(strict_types=1);

namespace App\Theme;

final readonly class ThemePublishResult
{
    public function __construct(
        private string $themeName,
        private string $channel
    ) {
    }

    public function themeName(): string
    {
        return $this->themeName;
    }

    public function channel(): string
    {
        return $this->channel;
    }
}
