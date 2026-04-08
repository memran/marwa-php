<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Support\ThemeSwitcher;
use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ResponseInterface;

abstract class BackendController extends Controller
{
    /**
     * @return array<int, array{label: string, hint: string, url: string, active: bool}>
     */
    protected function adminMenu(): array
    {
        return [
            ['label' => 'Overview', 'hint' => 'Dashboard', 'url' => '/admin', 'active' => true],
            ['label' => 'Modules', 'hint' => 'Packages', 'url' => '/admin#modules', 'active' => false],
            ['label' => 'Themes', 'hint' => 'Preview', 'url' => '/admin#themes', 'active' => false],
            ['label' => 'Settings', 'hint' => 'Config', 'url' => '/admin#settings', 'active' => false],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function renderBackend(string $template, array $data = []): ResponseInterface
    {
        $themeSwitcher = app(ThemeSwitcher::class);
        $theme = $themeSwitcher->applyToView(
            $themeSwitcher->adminTheme(),
            $this->themePreviewName(),
            $this->previewFlag()
        );

        app(ViewAdapter::class)->getView()->share('_admin_theme', $theme);
        app(ViewAdapter::class)->getView()->share('_admin_menu', $this->adminMenu());

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
