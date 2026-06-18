<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

final class RoleModuleNotice
{
    public function pull(string $key): ?string
    {
        $value = session($key);

        if (!is_string($value) || $value === '') {
            return null;
        }

        session()->forget($key);

        return $value;
    }

    public function flash(string $key, string $message): void
    {
        session()->flash($key, $message);
    }
}
