<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Auth\Contracts\AdminActorInterface;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Contracts\AdminUserProviderInterface;

final class AdminSessionManager
{
    private const SESSION_AUTHENTICATED = 'admin_authenticated';
    private const SESSION_USER_NAME = 'admin_user_name';
    private const SESSION_USER_EMAIL = 'admin_user_email';
    private const DEFAULT_ADMIN_NAME = 'Administrator';

    private ?string $lastFailureReason = null;
    private readonly AdminUserProviderInterface $users;
    private readonly LoginAttemptTracker $loginTracker;

    public function __construct(
        $users = null,
        ?LoginAttemptTracker $loginTracker = null
    ) {
        $this->users = $users instanceof AdminUserProviderInterface
            ? $users
            : new NullAdminUserProvider();
        $this->loginTracker = $loginTracker ?? new LoginAttemptTracker();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?AdminActorInterface
    {
        if (!session(self::SESSION_AUTHENTICATED, false)) {
            return null;
        }

        $email = trim((string) session(self::SESSION_USER_EMAIL, ''));

        if ($email === '') {
            return null;
        }

        $persistedUser = $this->users->findPersistedUserByEmail($email);
        if ($persistedUser instanceof AdminAuthenticatableInterface) {
            return $persistedUser;
        }

        if (!$this->loginTracker->matchesConfiguredEmail($email)) {
            return null;
        }

        return $this->users->createBootstrapUser(
            trim((string) session(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME)) ?: self::DEFAULT_ADMIN_NAME,
            $email
        );
    }

    public function attempt(string $email, string $password): bool
    {
        $email = trim($email);
        $this->lastFailureReason = null;

        $user = $this->users->findPersistedUserByEmail($email);

        if ($user instanceof AdminAuthenticatableInterface) {
            $hash = $user->getPasswordHash();

            if ($hash !== null && password_verify($password, $hash)) {
                $this->loginTracker->clearLoginFailures($email);
                session()->regenerate(true);
                session()->set(self::SESSION_AUTHENTICATED, true);
                session()->set(self::SESSION_USER_NAME, (string) $user->getAttribute('name'));
                session()->set(self::SESSION_USER_EMAIL, (string) $user->getAttribute('email'));

                $this->recordAuthActivity(
                    'auth.login',
                    'Signed in to the admin console.',
                    [
                        'summary' => 'Signed in to the admin console.',
                        'state' => [
                            'Email' => $email,
                        ],
                    ]
                );

                try {
                    $user->recordSuccessfulLogin(date('Y-m-d H:i:s'));
                } catch (\Throwable) {
                    // Best effort — login succeeds even if timestamp persist fails
                }

                return true;
            }
        }

        if ($this->loginTracker->matchesConfiguredEmail($email) && $this->loginTracker->matchesConfiguredPassword($password)) {
            $this->loginTracker->clearLoginFailures($email);
            session()->regenerate(true);
            session()->set(self::SESSION_AUTHENTICATED, true);
            session()->set(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME);
            session()->set(self::SESSION_USER_EMAIL, $this->loginTracker->configuredEmail());

            $this->recordAuthActivity(
                'auth.login',
                'Signed in to the admin console.',
                [
                    'summary' => 'Signed in to the admin console.',
                    'state' => [
                        'Email' => $email,
                    ],
                ]
            );

            return true;
        }

        if ($this->loginTracker->isLoginRateLimited($email)) {
            $this->lastFailureReason = 'rate_limited';

            return false;
        }

        $this->loginTracker->recordLoginFailure($email);
        $this->lastFailureReason = 'invalid_credentials';

        return false;
    }

    public function lastFailureReason(): ?string
    {
        return $this->lastFailureReason;
    }

    public function logout(): void
    {
        $this->recordAuthActivity(
            'auth.logout',
            'Signed out of the admin console.'
        );

        $session = session();
        $session->start();
        $session->forget(self::SESSION_AUTHENTICATED);
        $session->forget(self::SESSION_USER_NAME);
        $session->forget(self::SESSION_USER_EMAIL);
        $session->close();
    }

    /**
     * @param array<string, mixed> $details
     */
    private function recordAuthActivity(
        string $action,
        string $description,
        array $details = []
    ): void {
        event(new ActivityRecordingRequested(
            $action,
            $description,
            'auth',
            null,
            $details
        ));
    }
}
