<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Support\ThemeSwitcher;
use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ResponseInterface;

abstract class FrontendController extends Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function renderFrontend(string $template, array $data = []): ResponseInterface
    {
        $themeSwitcher = app(ThemeSwitcher::class);
        $theme = $themeSwitcher->applyToView(
            $themeSwitcher->frontendTheme(),
            $this->themePreviewName(),
            $this->previewFlag()
        );

        app(ViewAdapter::class)->getView()->share('_frontend_theme', $theme);
        app(ViewAdapter::class)->getView()->share('_frontend_themes', $themeSwitcher->frontendThemes());

        return $this->view($template, $data);
    }

    protected function themePreviewName(): ?string
    {
        $theme = Input::query('theme', null);
        $theme = is_string($theme) ? trim($theme) : null;

        if ($theme === null || $theme === '') {
            return null;
        }

        return in_array($theme, app(ThemeSwitcher::class)->frontendThemes(), true) ? $theme : null;
    }

    protected function previewFlag(): mixed
    {
        return Input::query('preview', null);
    }
}
