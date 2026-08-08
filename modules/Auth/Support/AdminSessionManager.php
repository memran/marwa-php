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
    private const SESSION_PASSWORD_FINGERPRINT = 'admin_password_fingerprint';
    private const SESSION_2FA_PENDING = 'admin_2fa_pending';
    private const SESSION_2FA_EMAIL = 'admin_2fa_email';
    private const SESSION_2FA_NAME = 'admin_2fa_name';
    private const SESSION_2FA_SECRET = 'admin_2fa_secret';
    private const SESSION_2FA_ENROL = 'admin_2fa_enrol';
    private const DEFAULT_ADMIN_NAME = 'Administrator';

    private ?string $lastFailureReason = null;
    private readonly AdminUserProviderInterface $users;
    private readonly LoginAttemptTracker $loginTracker;
    private readonly TwoFactorAuth $twoFactor;

    public function __construct(
        ?AdminUserProviderInterface $users = null,
        ?LoginAttemptTracker $loginTracker = null,
        ?TwoFactorAuth $twoFactor = null
    ) {
        $this->users = $users ?? new NullAdminUserProvider();
        $this->loginTracker = $loginTracker ?? new LoginAttemptTracker();
        $this->twoFactor = $twoFactor ?? new TwoFactorAuth(new TwoFactorAuthService());
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
            if (!$this->sessionPasswordMatches($persistedUser)) {
                $this->clearSession();

                return null;
            }

            return $persistedUser;
        }

        if (!$this->loginTracker->matchesConfiguredEmail($email)) {
            return null;
        }

        $bootstrapUser = $this->users->createBootstrapUser(
            trim((string) session(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME)) ?: self::DEFAULT_ADMIN_NAME,
            $email
        );

        if ($bootstrapUser->getPasswordHash() !== null && !is_string(session(self::SESSION_PASSWORD_FINGERPRINT))) {
            session()->set(self::SESSION_PASSWORD_FINGERPRINT, $this->passwordFingerprint($bootstrapUser));
        }

        return $bootstrapUser;
    }

    public function attempt(string $email, string $password, string $ipAddress = ''): bool
    {
        $email = trim($email);
        $this->lastFailureReason = null;

        if ($this->loginTracker->isLoginRateLimited($email, $ipAddress)) {
            $this->lastFailureReason = 'rate_limited';

            return false;
        }

        $user = $this->users->findPersistedUserByEmail($email);

        if ($user instanceof AdminAuthenticatableInterface) {
            $hash = $user->getPasswordHash();

            if ($hash !== null && password_verify($password, $hash)) {
                $this->loginTracker->clearLoginFailures($email, $ipAddress);

                if ($this->twoFactor->needsChallenge($user)) {
                    $this->beginTwoFactorChallenge(
                        $user,
                        $this->twoFactor->hasTwoFactor($user) ? 'verify' : 'enrol',
                        $this->twoFactor->hasTwoFactor($user)
                            ? (string) $this->twoFactor->secretFor($user)
                            : $this->twoFactor->generateSecret()
                    );

                    return true;
                }

                $this->startSession(
                    (string) $user->getAttribute('name'),
                    (string) $user->getAttribute('email'),
                    $this->passwordFingerprint($user)
                );

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
            $configuredEmail = $this->loginTracker->configuredEmail();
            if ($configuredEmail === null) {
                $this->recordFailedAttempt($email, $ipAddress);

                return false;
            }

            $this->loginTracker->clearLoginFailures($email, $ipAddress);
            $this->startSession(self::DEFAULT_ADMIN_NAME, $configuredEmail);

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

        $this->recordFailedAttempt($email, $ipAddress);

        return false;
    }

    public function refreshSessionFor(AdminAuthenticatableInterface $user): void
    {
        if (!session(self::SESSION_AUTHENTICATED, false)) {
            return;
        }

        session()->set(self::SESSION_PASSWORD_FINGERPRINT, $this->passwordFingerprint($user));
    }

    public function lastFailureReason(): ?string
    {
        return $this->lastFailureReason;
    }

    public function twoFactorChallengePending(): bool
    {
        return session(self::SESSION_2FA_PENDING, false) === true
            && session(self::SESSION_2FA_EMAIL, '') !== '';
    }

    public function twoFactorEmail(): ?string
    {
        $email = trim((string) session(self::SESSION_2FA_EMAIL, ''));

        return $email !== '' ? $email : null;
    }

    public function twoFactorSecret(): ?string
    {
        $secret = trim((string) session(self::SESSION_2FA_SECRET, ''));

        return $secret !== '' ? $secret : null;
    }

    public function twoFactorEnrolling(): bool
    {
        return session(self::SESSION_2FA_ENROL, false) === true;
    }

    public function cancelTwoFactorChallenge(): void
    {
        $session = session();
        $session->start();
        $session->forget(self::SESSION_2FA_PENDING);
        $session->forget(self::SESSION_2FA_EMAIL);
        $session->forget(self::SESSION_2FA_NAME);
        $session->forget(self::SESSION_2FA_SECRET);
        $session->forget(self::SESSION_2FA_ENROL);
        $session->close();
    }

    public function completeTwoFactor(string $code): bool
    {
        if (!$this->twoFactorChallengePending()) {
            return false;
        }

        $email = $this->twoFactorEmail();
        $secret = $this->twoFactorSecret();
        $enrolling = $this->twoFactorEnrolling();

        if ($email === null || $secret === null || !$this->twoFactor->verifyPending($secret, $code)) {
            return false;
        }

        $this->cancelTwoFactorChallenge();

        $user = $this->users->findPersistedUserByEmail($email);

        if (!$user instanceof AdminAuthenticatableInterface) {
            return false;
        }

        if ($enrolling && method_exists($user, 'enableTwoFactor')) {
            $user->enableTwoFactor($secret);
        }

        $this->startSession(
            (string) $user->getAttribute('name'),
            (string) $user->getAttribute('email'),
            $this->passwordFingerprint($user)
        );

        $this->recordAuthActivity(
            'auth.login',
            'Signed in to the admin console.',
            [
                'summary' => 'Signed in to the admin console.',
                'state' => [
                    'Email' => $email,
                    'Two-factor' => 'TOTP',
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

    public function logout(): void
    {
        $this->recordAuthActivity(
            'auth.logout',
            'Signed out of the admin console.'
        );

        $this->clearSession();
    }

    private function clearSession(): void
    {
        $session = session();
        $session->start();
        $session->forget(self::SESSION_AUTHENTICATED);
        $session->forget(self::SESSION_USER_NAME);
        $session->forget(self::SESSION_USER_EMAIL);
        $session->forget(self::SESSION_PASSWORD_FINGERPRINT);
        $session->forget(self::SESSION_2FA_PENDING);
        $session->forget(self::SESSION_2FA_EMAIL);
        $session->forget(self::SESSION_2FA_NAME);
        $session->forget(self::SESSION_2FA_SECRET);
        $session->forget(self::SESSION_2FA_ENROL);
        $session->regenerate(true);
        $session->close();
    }

    private function beginTwoFactorChallenge(AdminAuthenticatableInterface $user, string $type, string $secret): void
    {
        $session = session();
        $session->start();
        $session->set(self::SESSION_2FA_PENDING, true);
        $session->set(self::SESSION_2FA_EMAIL, (string) $user->getAttribute('email'));
        $session->set(self::SESSION_2FA_NAME, (string) $user->getAttribute('name'));
        $session->set(self::SESSION_2FA_ENROL, $type === 'enrol');
        $session->set(self::SESSION_2FA_SECRET, $secret);
        $session->close();
    }

    private function startSession(string $name, string $email, ?string $passwordFingerprint = null): void
    {
        session()->regenerate(true);
        session()->set(self::SESSION_AUTHENTICATED, true);
        session()->set(self::SESSION_USER_NAME, $name);
        session()->set(self::SESSION_USER_EMAIL, $email);

        if ($passwordFingerprint !== null) {
            session()->set(self::SESSION_PASSWORD_FINGERPRINT, $passwordFingerprint);
        }
    }

    private function sessionPasswordMatches(AdminAuthenticatableInterface $user): bool
    {
        $expected = $this->passwordFingerprint($user);
        $current = session(self::SESSION_PASSWORD_FINGERPRINT);

        return is_string($current) && $current !== '' && hash_equals($expected, $current);
    }

    private function passwordFingerprint(AdminAuthenticatableInterface $user): string
    {
        return hash('sha256', (string) $user->getPasswordHash());
    }

    private function recordFailedAttempt(string $email, string $ipAddress): void
    {
        $this->loginTracker->recordLoginFailure($email, $ipAddress);
        $this->lastFailureReason = 'invalid_credentials';
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
