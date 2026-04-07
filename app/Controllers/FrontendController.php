<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Support\ThemeSwitcher;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ResponseInterface;

abstract class FrontendController extends Controller
{
    protected function renderFrontend(string $template, array $data = []): ResponseInterface
    {
        $themeSwitcher = app(ThemeSwitcher::class);

        $themeSwitcher->applyToView(
            $themeSwitcher->frontendTheme(),
            $this->themePreviewName(),
            $this->previewFlag()
        );

        return $this->view($template, $data);
    }

    protected function themePreviewName(): ?string
    {
        $theme = Input::query('theme', null);

        return is_string($theme) ? trim($theme) : null;
    }

    protected function previewFlag(): mixed
    {
        return Input::query('preview', null);
    }
}
