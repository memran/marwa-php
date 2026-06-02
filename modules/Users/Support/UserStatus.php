<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

enum UserStatus: string
{
    case All = 'all';
    case Active = 'active';
    case Disabled = 'disabled';
    case Trashed = 'trashed';

    public static function tryFromFilter(?string $value): self
    {
        if ($value === null) {
            return self::All;
        }

        return self::tryFrom($value) ?? self::All;
    }

    public function label(): string
    {
        return match ($this) {
            self::All => 'All',
            self::Active => 'Active',
            self::Disabled => 'Disabled',
            self::Trashed => 'Trashed',
        };
    }
}
