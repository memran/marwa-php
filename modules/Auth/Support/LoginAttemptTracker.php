<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

final class LoginAttemptTracker
{
    private const SESSION_LOGIN_FAILURES = 'admin_login_failures';
    private const DEFAULT_ADMIN_EMAIL = 'admin@marwa.test';
    private const DEFAULT_ADMIN_PASSWORD = 'ExampleAdminPassword123!';

    public function configuredEmail(): string
    {
        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', self::DEFAULT_ADMIN_EMAIL));

        return $email !== '' ? $email : self::DEFAULT_ADMIN_EMAIL;
    }

    public function configuredPassword(): string
    {
        $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', self::DEFAULT_ADMIN_PASSWORD);

        return $password !== '' ? $password : self::DEFAULT_ADMIN_PASSWORD;
    }

    public function loginAttemptLimit(): int
    {
        return max(1, (int) config('settings.lifecycle.security.login_attempt_limit', 5));
    }

    public function loginFailures(string $email): int
    {
        $failures = session(self::SESSION_LOGIN_FAILURES, []);

        if (!is_array($failures)) {
            return 0;
        }

        return max(0, (int) ($failures[$this->loginFailureKey($email)] ?? 0));
    }

    public function recordLoginFailure(string $email): void
    {
        $failures = session(self::SESSION_LOGIN_FAILURES, []);

        if (!is_array($failures)) {
            $failures = [];
        }

        $key = $this->loginFailureKey($email);
        $failures[$key] = $this->loginFailures($email) + 1;
        session()->set(self::SESSION_LOGIN_FAILURES, $failures);
    }

    public function clearLoginFailures(string $email): void
    {
        $failures = session(self::SESSION_LOGIN_FAILURES, []);

        if (!is_array($failures)) {
            return;
        }

        $key = $this->loginFailureKey($email);

        if (!array_key_exists($key, $failures)) {
            return;
        }

        unset($failures[$key]);
        session()->set(self::SESSION_LOGIN_FAILURES, $failures);
    }

    public function isLoginRateLimited(string $email): bool
    {
        return $this->loginFailures($email) >= $this->loginAttemptLimit();
    }

    public function matchesConfiguredEmail(string $email): bool
    {
        return $email !== '' && strcasecmp($email, $this->configuredEmail()) === 0;
    }

    public function matchesConfiguredPassword(string $password): bool
    {
        return hash_equals($this->configuredPassword(), $password);
    }

    private function loginFailureKey(string $email): string
    {
        return strtolower(trim($email));
    }
}
