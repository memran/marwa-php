<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Roles\Support\RoleActivityLogger;
use App\Modules\Auth\Models\Role;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class ActivityRecordingRequestedPublisherTest extends TestCase
{
    public function testRoleLoggerDispatchesActivityRequestEvent(): void
    {
        $app = new Application(__DIR__ . '/../../');
        $GLOBALS['marwa_app'] = $app;

        $dispatcher = new class implements EventDispatcherInterface {
            /** @var list<object> */
            public array $events = [];

            public function dispatch(object $event): object
            {
                $this->events[] = $event;

                return $event;
            }

            public function listen(string $event, callable|array|string $listener, int $priority = 0): void
            {
            }
        };

        $app->container()->addShared(EventDispatcherAdapter::class, $dispatcher, true);

        $logger = new RoleActivityLogger();
        $role = Role::newInstance([
            'id' => 7,
            'name' => 'Admin',
            'slug' => 'admin',
        ], true);

        $logger->roleCreated($role, ['name' => 'Admin']);

        self::assertCount(1, $dispatcher->events);
        self::assertInstanceOf(ActivityRecordingRequested::class, $dispatcher->events[0]);
        self::assertSame('role.created', $dispatcher->events[0]->action);
        self::assertSame('Created role.', $dispatcher->events[0]->description);
        self::assertSame('role', $dispatcher->events[0]->subjectType);
        self::assertSame(7, $dispatcher->events[0]->subjectId);
    }
}
