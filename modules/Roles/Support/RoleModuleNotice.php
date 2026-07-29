<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

final class RoleModuleNotice
{
    public function flash(string $key, string $message): void
    {
        session()->flash($key, $message);
    }
}
