<?php

declare(strict_types=1);

namespace App\View\Extensions;

use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\View\Extension\AbstractViewExtension;

final class NavigationViewExtension extends AbstractViewExtension implements ViewExtensionInterface
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->addFunction('menu_tree', static fn (): array => app()->has(MenuRegistry::class) ? app(MenuRegistry::class)->tree() : [], true);
        $this->addFunction('main_menu', static fn (): array => app()->has(MenuRegistry::class) ? app(MenuRegistry::class)->tree() : [], true);
        $this->registered = true;
    }
}
