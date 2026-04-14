<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use Marwa\Framework\Database\Model;

final class Notification extends Model
{
    protected static ?string $table = 'notifications';

    protected static array $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
        'read_at',
        'action_url',
    ];

    protected static array $casts = [
        'is_read' => 'int',
        'read_at' => 'datetime',
    ];

    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';

    public static function types(): array
    {
        return [
            self::TYPE_INFO => 'Info',
            self::TYPE_SUCCESS => 'Success',
            self::TYPE_WARNING => 'Warning',
            self::TYPE_ERROR => 'Error',
        ];
    }

    public function markAsRead(): bool
    {
        if ($this->getAttribute('is_read')) {
            return true;
        }

        $this->fill([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
        
        return $this->update();
    }

    public function isUnread(): bool
    {
        return (int) $this->getAttribute('is_read') === 0;
    }
}