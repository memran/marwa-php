<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\PaginatesRows;
use Marwa\Framework\Database\Model as FrameworkModel;
use Marwa\DB\Support\Collection;

abstract class Model extends FrameworkModel
{
    use PaginatesRows;

    /**
     * Collect all rows, including trashed ones, so callers can filter once in memory.
     */
    public static function collect(): Collection
    {
        return new Collection(static::withTrashed()->get());
    }
}
