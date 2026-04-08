<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

class Controller extends \App\Http\Controllers\Controller
{
    protected function themeName(): string
    {
        return trim((string) config('view.adminTheme', 'admin')) ?: 'admin';
    }
}
