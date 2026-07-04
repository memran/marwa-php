<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\LoginAttemptTracker;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\CacheInterface;
use PHPUnit\Framework\TestCase;

final class LoginAttemptTrackerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-login-throttle-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);

        $GLOBALS['marwa_app'] = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
    }

    public function testItUsesCacheIncrementWithLoginWindowTtl(): void
    {
        $cache = new RecordingThrottleCache();
        $tracker = new LoginAttemptTracker($cache);

        $tracker->recordLoginFailure('Admin@Example.test', '127.0.0.1');
        $tracker->recordLoginFailure('admin@example.test', '127.0.0.1');

        self::assertSame(2, $tracker->loginFailures('admin@example.test', '127.0.0.1'));
        self::assertSame([900, 900], $cache->incrementTtls);
        self::assertFalse($tracker->isLoginRateLimited('admin@example.test', '127.0.0.1'));
    }

    public function testItClearsLoginFailuresAfterSuccessfulLogin(): void
    {
        $cache = new RecordingThrottleCache();
        $tracker = new LoginAttemptTracker($cache);

        $tracker->recordLoginFailure('admin@example.test', '127.0.0.1');
        $tracker->clearLoginFailures('admin@example.test', '127.0.0.1');

        self::assertSame(0, $tracker->loginFailures('admin@example.test', '127.0.0.1'));
        self::assertCount(1, $cache->forgottenKeys);
    }

    public function testItRequiresConfiguredCacheDriver(): void
    {
        $tracker = new LoginAttemptTracker();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Login throttling requires a configured cache driver.');

        $tracker->recordLoginFailure('admin@example.test', '127.0.0.1');
    }
}

final class RecordingThrottleCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * @var list<int>
     */
    public array $incrementTtls = [];

    /**
     * @var list<string>
     */
    public array $forgottenKeys = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function put(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function forget(string $key): bool
    {
        $this->forgottenKeys[] = $key;
        unset($this->values[$key]);

        return true;
    }

    public function flush(): bool
    {
        $this->values = [];

        return true;
    }

    public function remember(string $key, null|int|\DateInterval $ttl, \Closure $callback): mixed
    {
        if (!$this->has($key)) {
            $this->values[$key] = $callback();
        }

        return $this->values[$key];
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value);
    }

    public function putMany(array $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->values[$key] = $value;
        }

        return true;
    }

    public function many(array $keys, mixed $default = null): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->values[$key] ?? $default;
        }

        return $values;
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        $this->incrementTtls[] = $ttl;
        $value = $this->values[$key] ?? $initial;

        if (!is_int($value) && !is_numeric($value)) {
            return false;
        }

        $value = (int) $value;
        $value += $offset;
        $this->values[$key] = $value;

        return $value;
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        $value = $this->values[$key] ?? $initial;

        if (!is_int($value) && !is_numeric($value)) {
            return false;
        }

        $value = (int) $value;
        $value -= $offset;
        $this->values[$key] = $value;

        return $value;
    }
}
