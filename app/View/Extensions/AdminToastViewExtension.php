<?php

declare(strict_types=1);

namespace App\View\Extensions;

use App\Support\AdminToast;
use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Views\Extension\AbstractViewExtension;

final class AdminToastViewExtension extends AbstractViewExtension implements ViewExtensionInterface
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->addFunction('admin_toasts', static fn(): array => AdminToast::fromSession(), true);
        $this->registered = true;
    }
}
