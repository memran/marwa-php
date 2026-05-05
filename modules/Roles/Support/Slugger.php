<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

final class Slugger
{
    public function slugify(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? $fallback : $value;
    }
}
