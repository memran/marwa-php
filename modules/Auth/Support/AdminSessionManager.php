<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Users\Models\User;

final class AdminSessionManager
{
    private const SESSION_AUTHENTICATED = 'admin_authenticated';
    private const SESSION_USER_NAME = 'admin_user_name';
    private const SESSION_USER_EMAIL = 'admin_user_email';
    private const DEFAULT_ADMIN_NAME = 'Administrator';

    private ?string $lastFailureReason = null;

    public function __construct(
        private readonly AdminUserResolver $users,
        private readonly LoginAttemptTracker $loginTracker
    ) {
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        if (!session(self::SESSION_AUTHENTICATED, false)) {
            return null;
        }

        $email = trim((string) session(self::SESSION_USER_EMAIL, ''));

        if ($email === '') {
            return null;
        }

        $persistedUser = $this->users->findPersistedUserByEmail($email);
        if ($persistedUser instanceof User) {
            return $persistedUser;
        }

        if (!$this->loginTracker->matchesConfiguredEmail($email)) {
            return null;
        }

        $fallbackUser = User::newInstance([
            'id' => 0,
            'name' => trim((string) session(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME)) ?: self::DEFAULT_ADMIN_NAME,
            'email' => $email,
            'role_id' => $this->users->adminRoleId(),
            'role' => 'admin',
            'is_active' => true,
        ], false);

        return $fallbackUser;
    }

    public function attempt(string $email, string $password): bool
    {
        $email = trim($email);
        $this->lastFailureReason = null;

        $user = $this->users->findPersistedUserByEmail($email);

        if ($user instanceof User) {
            $hash = $user->getPasswordHash();

            if ($hash !== null && password_verify($password, $hash)) {
                $this->loginTracker->clearLoginFailures($email);
                session()->regenerate(true);
                session()->set(self::SESSION_AUTHENTICATED, true);
                session()->set(self::SESSION_USER_NAME, (string) $user->getAttribute('name'));
                session()->set(self::SESSION_USER_EMAIL, (string) $user->getAttribute('email'));

                (new ActivityRecorder())->recordActorAction(
                    'auth.login',
                    'Signed in to the admin console.',
                    $user,
                    'auth',
                    null,
                    [
                        'summary' => 'Signed in to the admin console.',
                        'state' => [
                            'Email' => $email,
                        ],
                    ]
                );

                return true;
            }
        }

        if ($this->loginTracker->matchesConfiguredEmail($email) && $this->loginTracker->matchesConfiguredPassword($password)) {
            $this->loginTracker->clearLoginFailures($email);
            session()->regenerate(true);
            session()->set(self::SESSION_AUTHENTICATED, true);
            session()->set(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME);
            session()->set(self::SESSION_USER_EMAIL, $this->loginTracker->configuredEmail());

            $user = $this->user();
            (new ActivityRecorder())->recordActorAction(
                'auth.login',
                'Signed in to the admin console.',
                $user,
                'auth',
                null,
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
        $actor = $this->user();

        (new ActivityRecorder())->recordActorAction(
            'auth.logout',
            'Signed out of the admin console.',
            $actor,
            'auth'
        );

        $session = session();
        $session->start();
        $session->forget(self::SESSION_AUTHENTICATED);
        $session->forget(self::SESSION_USER_NAME);
        $session->forget(self::SESSION_USER_EMAIL);
        $session->invalidate();
    }
}
