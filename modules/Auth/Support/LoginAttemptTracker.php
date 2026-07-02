<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use Marwa\Framework\Contracts\CacheInterface;

final class LoginAttemptTracker
{
    private const CACHE_PREFIX = 'auth-login';

    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    public function bootstrapEnabled(): bool
    {
        return (bool) env('ADMIN_BOOTSTRAP_ENABLED', in_array((string) env('APP_ENV', 'production'), ['local', 'testing'], true));
    }

    public function configuredEmail(): ?string
    {
        if (!$this->bootstrapEnabled()) {
            return null;
        }

        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', ''));

        return $email !== '' ? $email : null;
    }

    public function configuredPassword(): ?string
    {
        if (!$this->bootstrapEnabled()) {
            return null;
        }

        $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', '');

        return $password !== '' ? $password : null;
    }

    public function loginAttemptLimit(): int
    {
        return max(1, (int) config('settings.lifecycle.security.login_attempt_limit', 5));
    }

    public function loginAttemptWindow(): int
    {
        return max(60, (int) config('settings.lifecycle.security.login_attempt_window', 900));
    }

    public function loginFailures(string $email, string $ipAddress = ''): int
    {
        return (int) ($this->cache?->get($this->cacheKey($email, $ipAddress), 0) ?? 0);
    }

    public function recordLoginFailure(string $email, string $ipAddress = ''): void
    {
        $key = $this->cacheKey($email, $ipAddress);
        $failures = $this->loginFailures($email, $ipAddress) + 1;

        $this->cache?->put($key, $failures, $this->loginAttemptWindow());
    }

    public function clearLoginFailures(string $email, string $ipAddress = ''): void
    {
        $this->cache?->forget($this->cacheKey($email, $ipAddress));
    }

    public function isLoginRateLimited(string $email, string $ipAddress = ''): bool
    {
        return $this->loginFailures($email, $ipAddress) >= $this->loginAttemptLimit();
    }

    public function matchesConfiguredEmail(string $email): bool
    {
        $configuredEmail = $this->configuredEmail();

        return $configuredEmail !== null && $email !== '' && strcasecmp($email, $configuredEmail) === 0;
    }

    public function matchesConfiguredPassword(string $password): bool
    {
        $configuredPassword = $this->configuredPassword();

        return $configuredPassword !== null && hash_equals($configuredPassword, $password);
    }

    private function cacheKey(string $email, string $ipAddress): string
    {
        return self::CACHE_PREFIX . '-' . hash('sha256', strtolower(trim($email)) . '|' . trim($ipAddress));
    }
}
