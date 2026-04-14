<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Models;

use Marwa\Framework\Database\Model;

final class DashboardWidget extends Model
{
    protected static ?string $table = 'dashboard_widgets';

    protected static array $fillable = [
        'user_id',
        'widget_id',
        'widget_type',
        'title',
        'position',
        'width',
        'enabled',
        'config',
    ];

    protected static array $casts = [
        'enabled' => 'bool',
        'user_id' => 'int',
        'position' => 'int',
    ];
}