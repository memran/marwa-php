<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\PasswordResetThrottle;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\CacheInterface;
use PHPUnit\Framework\TestCase;

final class PasswordResetThrottleTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-password-reset-throttle-' . bin2hex(random_bytes(6));
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

    public function testItUsesCacheIncrementWithResetWindowTtl(): void
    {
        $cache = new RecordingPasswordResetThrottleCache();
        $throttle = new PasswordResetThrottle($cache);

        $throttle->recordRecoveryRequest('Admin@Example.test', '127.0.0.1');
        $throttle->recordRecoveryRequest('admin@example.test', '127.0.0.1');

        self::assertSame(2, $throttle->recoveryRequests('admin@example.test', '127.0.0.1'));
        self::assertSame([900, 900], $cache->incrementTtls);
        self::assertFalse($throttle->isRecoveryRateLimited('admin@example.test', '127.0.0.1'));

        $throttle->recordRecoveryRequest('admin@example.test', '127.0.0.1');

        self::assertTrue($throttle->isRecoveryRateLimited('admin@example.test', '127.0.0.1'));
    }

    public function testItRequiresConfiguredCacheDriver(): void
    {
        $throttle = new PasswordResetThrottle();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Password reset throttling requires a configured cache driver.');

        $throttle->recordRecoveryRequest('admin@example.test', '127.0.0.1');
    }
}

final class RecordingPasswordResetThrottleCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * @var list<int>
     */
    public array $incrementTtls = [];

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
