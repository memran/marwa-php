<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;

/**
 * Resolves the application two-factor authentication policy and user state.
 */
final class TwoFactorAuth
{
    public const MODE_DISABLED = 'disabled';
    public const MODE_OPTIONAL = 'optional';
    public const MODE_REQUIRED = 'required';

    /**
     * @var list<string>
     */
    private const MODES = [
        self::MODE_DISABLED,
        self::MODE_OPTIONAL,
        self::MODE_REQUIRED,
    ];

    /**
     * @var \Closure(): string|null
     */
    private readonly ?\Closure $modeProvider;

    public function __construct(
        private readonly TwoFactorAuthService $totp,
        ?\Closure $modeProvider = null,
    ) {
        $this->modeProvider = $modeProvider;
    }

    public function mode(): string
    {
        $mode = $this->modeProvider !== null
            ? trim((string) ($this->modeProvider)())
            : trim((string) config('settings.security.2fa_mode', ''));

        if (!in_array($mode, self::MODES, true)) {
            return self::MODE_DISABLED;
        }

        return $mode;
    }

    public function isDisabled(): bool
    {
        return $this->mode() === self::MODE_DISABLED;
    }

    public function isOptional(): bool
    {
        return $this->mode() === self::MODE_OPTIONAL;
    }

    public function isRequired(): bool
    {
        return $this->mode() === self::MODE_REQUIRED;
    }

    public function isEnabled(): bool
    {
        return !$this->isDisabled();
    }

    public function hasTwoFactor(AdminAuthenticatableInterface $user): bool
    {
        $enabled = $this->readUserFlag($user, 'hasTwoFactorEnabled');

        if (!$enabled) {
            return false;
        }

        $secret = $this->secretFor($user);

        return is_string($secret) && $secret !== '';
    }

    /**
     * Whether a login must continue through a TOTP step for this user.
     */
    public function needsChallenge(AdminAuthenticatableInterface $user): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        if ($this->hasTwoFactor($user)) {
            return true;
        }

        return $this->isRequired();
    }

    public function secretFor(AdminAuthenticatableInterface $user): ?string
    {
        if (method_exists($user, 'getTwoFactorSecret')) {
            $secret = $user->getTwoFactorSecret();

            if (is_string($secret) && trim($secret) !== '') {
                return $secret;
            }
        }

        $attribute = method_exists($user, 'getAttribute')
            ? $user->getAttribute('two_factor_secret')
            : null;

        return is_string($attribute) && trim($attribute) !== ''
            ? $attribute
            : null;
    }

    public function generateSecret(int $length = 20): string
    {
        return $this->totp->generateSecret($length);
    }

    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        return $this->totp->provisioningUri($secret, $accountName, $issuer);
    }

    public function verifyCode(AdminAuthenticatableInterface $user, string $code): bool
    {
        $secret = $this->secretFor($user);

        if ($secret === null) {
            return false;
        }

        return $this->totp->verify($secret, $code);
    }

    public function verifyPending(string $secret, string $code): bool
    {
        return $this->totp->verify($secret, $code);
    }

    private function readUserFlag(AdminAuthenticatableInterface $user, string $method): bool
    {
        if (!method_exists($user, $method)) {
            return false;
        }

        $flag = $user->{$method}();

        return is_bool($flag) ? $flag : (bool) $flag;
    }
}