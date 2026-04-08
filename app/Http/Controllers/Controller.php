<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Marwa\Framework\Views\View as FrameworkView;

class Controller extends \Marwa\Framework\Controllers\Controller
{
    protected function render(string $template, array $data = []): string
    {
        /** @var FrameworkView $view */
        $view = app(FrameworkView::class);
        $previousTheme = $view->theme();
        $themeName = $this->themeName();

        if ($themeName !== '') {
            $view->theme($themeName);
        }

        try {
            return parent::render($template, $data);
        } finally {
            if ($previousTheme !== '') {
                $view->theme($previousTheme);
            }
        }
    }

    protected function themeName(): string
    {
        return trim((string) config('view.frontendTheme', 'default')) ?: 'default';
    }
}
