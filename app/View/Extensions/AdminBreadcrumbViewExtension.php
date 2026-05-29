<?php

declare(strict_types=1);

namespace App\View\Extensions;

use App\Support\AdminBreadcrumbs;
use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Views\Extension\AbstractViewExtension;

final class AdminBreadcrumbViewExtension extends AbstractViewExtension implements ViewExtensionInterface
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->addFunction(
            'admin_breadcrumbs',
            static fn (?string $path = null): array => AdminBreadcrumbs::fromRequestPath($path),
            true
        );
        $this->registered = true;
    }
}
