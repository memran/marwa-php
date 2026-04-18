<?php

declare(strict_types=1);

namespace App\View\Extensions;

use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Views\Extension\AbstractViewExtension;

final class SecurityViewExtension extends AbstractViewExtension implements ViewExtensionInterface
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->addFunction('csrf_field', static fn (): string => csrf_field(), true);
        $this->registered = true;
    }
}
