<?php

declare(strict_types=1);

namespace App\Theme;

interface AdminThemePersistence
{
    public function publish(string $themeName): void;
}
