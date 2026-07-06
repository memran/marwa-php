<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use Marwa\Framework\Contracts\CacheInterface;

final class PasswordResetThrottle
{
    private const CACHE_PREFIX = 'auth-password-reset';

    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    public function resetAttemptLimit(): int
    {
        return max(1, (int) config('settings.lifecycle.security.password_reset_attempt_limit', 3));
    }

    public function resetAttemptWindow(): int
    {
        return max(60, (int) config('settings.lifecycle.security.password_reset_attempt_window', 900));
    }

    public function isRecoveryRateLimited(string $email, string $ipAddress = ''): bool
    {
        return $this->recoveryRequests($email, $ipAddress) >= $this->resetAttemptLimit();
    }

    public function recoveryRequests(string $email, string $ipAddress = ''): int
    {
        return (int) ($this->cache()->get($this->cacheKey($email, $ipAddress), 0) ?? 0);
    }

    public function recordRecoveryRequest(string $email, string $ipAddress = ''): void
    {
        $requests = $this->cache()->increment(
            $this->cacheKey($email, $ipAddress),
            1,
            0,
            $this->resetAttemptWindow()
        );

        if ($requests === false) {
            throw new \RuntimeException('Unable to increment password reset throttle counter.');
        }
    }

    private function cacheKey(string $email, string $ipAddress): string
    {
        return self::CACHE_PREFIX . '-' . hash('sha256', strtolower(trim($email)) . '|' . trim($ipAddress));
    }

    private function cache(): CacheInterface
    {
        if ($this->cache instanceof CacheInterface) {
            return $this->cache;
        }

        throw new \RuntimeException('Password reset throttling requires a configured cache driver.');
    }
}
