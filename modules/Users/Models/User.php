<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Events\ActivityRecordRequested;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Support\UserActivityService;
use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';
    protected static bool $activityEventsRegistered = false;

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $casts = [
        'is_active' => 'int',
    ];

    protected static bool $softDeletes = true;

    public function boot(): void
    {
        if (static::$activityEventsRegistered) {
            return;
        }

        static::$activityEventsRegistered = true;

        static::created(static function (self $user): void {
            static::dispatchActivity($user, 'created');
        });

        static::updated(static function (self $user): void {
            static::dispatchActivity($user, 'updated');
        });

        static::deleted(static function (self $user): void {
            static::dispatchActivity($user, 'deleted');
        });

        static::restored(static function (self $user): void {
            static::dispatchActivity($user, 'restored');
        });
    }

    private static function dispatchActivity(self $user, string $event): void
    {
        $payload = static::activityService()->payloadFromModelEvent($user, $event);

        app()->dispatch(new ActivityRecordRequested(
            action: $payload['action'],
            description: $payload['description'],
            actor: static::currentActor(),
            subjectType: $payload['subjectType'],
            subjectId: $payload['subjectId'],
            details: $payload['details']
        ));
    }

    private static function activityService(): UserActivityService
    {
        return app(UserActivityService::class);
    }

    private static function currentActor(): ?self
    {
        return (new AuthManager())->user();
    }
}
