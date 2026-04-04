<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\PasswordReset;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Supports\Config;
use Marwa\Support\Security;

final class AuthManager
{
    private ?User $currentUser = null;

    public function __construct(
        private Config $config,
        private SessionInterface $session
    ) {
        $this->config->loadIfExists('auth.php');
    }

    public function user(): ?User
    {
        if ($this->currentUser instanceof User) {
            return $this->currentUser;
        }

        $userId = $this->session->get($this->sessionKey());

        if (is_numeric($userId)) {
            $user = User::query()->with('roles')->where('id', '=', (int) $userId)->first();

            if ($user instanceof User) {
                return $this->currentUser = $user;
            }
        }

        return $this->currentUser = $this->restoreRememberedUser();
    }

    public function check(): bool
    {
        return $this->user() instanceof User;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function login(User $user): void
    {
        $this->session->regenerate(true);
        $this->session->set($this->sessionKey(), (int) $user->getKey());
        $this->currentUser = $this->reloadUser($user);
    }

    public function logout(): void
    {
        if ($this->currentUser instanceof User) {
            $this->currentUser->fill([
                'remember_selector' => null,
                'remember_token_hash' => null,
                'remember_expires_at' => null,
            ]);
            $this->currentUser->save();
        }

        $this->session->forget($this->sessionKey());
        $this->session->forget($this->intendedKey());
        $this->session->regenerate(true);
        $this->currentUser = null;
    }

    public function attempt(string $email, string $password): ?User
    {
        $user = User::query()
            ->with('roles')
            ->where('email', '=', $this->normalizeEmail($email))
            ->where('status', '=', 1)
            ->first();

        if (!$user instanceof User) {
            return null;
        }

        if (!Security::verifyHash($password, (string) $user->getAttribute('password'))) {
            return null;
        }

        $this->recordSuccessfulLogin($user);
        $this->login($user);

        return $this->currentUser;
    }

    public function register(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => trim($name),
            'email' => $this->normalizeEmail($email),
            'password' => Security::hash($password),
            'status' => 1,
            'email_verified_at' => null,
            'remember_selector' => null,
            'remember_token_hash' => null,
            'remember_expires_at' => null,
            'last_login_at' => null,
        ]);

        $this->assignDefaultRole($user);
        $this->login($user);

        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $password): bool
    {
        if (!Security::verifyHash($currentPassword, (string) $user->getAttribute('password'))) {
            return false;
        }

        $user->fill([
            'password' => Security::hash($password),
            'remember_selector' => null,
            'remember_token_hash' => null,
            'remember_expires_at' => null,
        ]);

        return $user->save();
    }

    public function createPasswordResetToken(string $email): ?array
    {
        $user = $this->findActiveUserByEmail($email);

        if (!$user instanceof User) {
            return null;
        }

        $token = Security::randomString(64);
        $expiresAt = $this->passwordResetTtl();
        $record = PasswordReset::query()->where('email', '=', $user->getAttribute('email'))->first();

        if ($record instanceof PasswordReset) {
            $record->fill([
                'token_hash' => Security::hash($token),
                'expires_at' => $this->formatDateTime($expiresAt),
                'used_at' => null,
            ]);
            $record->save();
        } else {
            PasswordReset::create([
                'email' => (string) $user->getAttribute('email'),
                'token_hash' => Security::hash($token),
                'expires_at' => $this->formatDateTime($expiresAt),
                'used_at' => null,
            ]);
        }

        return [
            'token' => $token,
            'url' => $this->resetPasswordUrl($token),
            'expires_at' => $this->formatDateTime($expiresAt),
            'ttl' => $this->passwordResetTtl(),
            'email' => (string) $user->getAttribute('email'),
            'name' => (string) $user->getAttribute('name'),
        ];
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        $reset = PasswordReset::query()->where('email', '=', $this->normalizeEmail($email))->first();

        if (!$reset instanceof PasswordReset) {
            return false;
        }

        if ($this->isPasswordResetExpired($reset) || !Security::verifyHash($token, (string) $reset->getAttribute('token_hash'))) {
            return false;
        }

        $user = $this->findActiveUserByEmail($email);

        if (!$user instanceof User) {
            return false;
        }

        $user->fill([
            'password' => Security::hash($password),
            'remember_selector' => null,
            'remember_token_hash' => null,
            'remember_expires_at' => null,
        ]);
        $user->save();

        $reset->fill([
            'used_at' => $this->now(),
        ]);
        $reset->save();

        $this->logout();

        return true;
    }

    public function setIntendedUrl(string $url): void
    {
        $url = trim($url);

        if ($url === '') {
            return;
        }

        $this->session->set($this->intendedKey(), $url);
    }

    public function intendedUrl(string $default = '/admin'): string
    {
        $url = $this->session->get($this->intendedKey(), $default);

        if (!is_string($url) || trim($url) === '') {
            return $default;
        }

        return $url;
    }

    public function consumeIntendedUrl(string $default = '/admin'): string
    {
        $url = $this->intendedUrl($default);
        $this->session->forget($this->intendedKey());

        return $url;
    }

    public function assignDefaultRole(User $user): void
    {
        $role = $this->ensureRole((string) $this->config->get('auth.defaults.default_role', 'user'));

        if (!$role instanceof Role) {
            return;
        }

        $fresh = User::query()->with('roles')->where('id', '=', (int) $user->getKey())->first();

        if ($fresh instanceof User && $fresh->hasRole((string) $role->getAttribute('slug'))) {
            return;
        }

        $user->roles()->attach($user, (int) $role->getKey());
    }

    public function hasRole(User $user, string|array $roles): bool
    {
        $required = array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            is_array($roles) ? $roles : [$roles]
        )));

        if ($required === []) {
            return false;
        }

        $current = $this->roleSlugs($user);

        return array_intersect($required, $current) !== [];
    }

    public function isAdmin(?User $user = null): bool
    {
        $user ??= $this->user();

        return $user instanceof User && $this->hasRole($user, $this->adminRole());
    }

    public function rememberCookieHeader(User $user): string
    {
        $token = $this->issueRememberToken($user);

        return $this->buildCookieHeader(
            $this->rememberCookieName(),
            $this->encodeRememberToken($token['selector'], $token['validator']),
            $this->rememberTtl()
        );
    }

    public function forgetRememberCookieHeader(): string
    {
        return $this->buildCookieHeader($this->rememberCookieName(), '', -3600);
    }

    /**
     * @return array{selector:string,validator:string}|null
     */
    public static function decodeRememberToken(string $cookie): ?array
    {
        $cookie = trim($cookie);

        if ($cookie === '' || !str_contains($cookie, '.')) {
            return null;
        }

        [$selector, $validator] = array_pad(explode('.', $cookie, 2), 2, '');

        $selector = trim($selector);
        $validator = trim($validator);

        if ($selector === '' || $validator === '') {
            return null;
        }

        return [
            'selector' => $selector,
            'validator' => $validator,
        ];
    }

    public static function encodeRememberToken(string $selector, string $validator): string
    {
        return $selector . '.' . $validator;
    }

    /**
     * @param array{path?:string,secure?:bool,httpOnly?:bool,sameSite?:string,domain?:string|null} $options
     */
    public static function buildCookieHeader(string $name, string $value, int $maxAge, array $options = []): string
    {
        $parts = [
            rawurlencode($name) . '=' . rawurlencode($value),
            'Max-Age=' . $maxAge,
            'Path=' . ($options['path'] ?? '/'),
            'HttpOnly',
            'SameSite=' . ($options['sameSite'] ?? 'Lax'),
        ];

        $domain = $options['domain'] ?? null;

        if (is_string($domain) && trim($domain) !== '') {
            $parts[] = 'Domain=' . trim($domain);
        }

        if (($options['secure'] ?? false) === true) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }

    /**
     * @return array{selector:string,validator:string,expires_at:int}
     */
    private function issueRememberToken(User $user): array
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = time() + $this->rememberTtl();

        $user->fill([
            'remember_selector' => $selector,
            'remember_token_hash' => Security::hash($validator),
            'remember_expires_at' => $this->formatDateTime($expiresAt),
        ]);
        $user->save();

        return [
            'selector' => $selector,
            'validator' => $validator,
            'expires_at' => $expiresAt,
        ];
    }

    private function restoreRememberedUser(): ?User
    {
        $cookie = (string) ($_COOKIE[$this->rememberCookieName()] ?? '');
        $pair = self::decodeRememberToken($cookie);

        if ($pair === null) {
            return null;
        }

        $user = User::query()
            ->with('roles')
            ->where('remember_selector', '=', $pair['selector'])
            ->where('status', '=', 1)
            ->first();

        if (!$user instanceof User) {
            return null;
        }

        if ($this->isRememberExpired($user)) {
            return null;
        }

        if (!Security::verifyHash($pair['validator'], (string) $user->getAttribute('remember_token_hash'))) {
            return null;
        }

        $this->login($user);

        return $this->currentUser;
    }

    private function reloadUser(User $user): ?User
    {
        $fresh = User::query()->with('roles')->where('id', '=', (int) $user->getKey())->first();

        return $fresh instanceof User ? $fresh : $user;
    }

    private function recordSuccessfulLogin(User $user): void
    {
        $user->fill([
            'last_login_at' => $this->now(),
        ]);
        $user->save();
    }

    private function ensureRole(string $slug): ?Role
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $role = Role::query()->where('slug', '=', $slug)->first();

        if ($role instanceof Role) {
            return $role;
        }

        return Role::create([
            'name' => ucfirst(str_replace(['-', '_'], ' ', $slug)),
            'slug' => $slug,
            'description' => $slug === $this->adminRole()
                ? 'Full access to the administration area.'
                : 'Default starter role.',
            'is_default' => $slug === $this->defaultRole(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function roleSlugs(User $user): array
    {
        $roles = $user->relationLoaded('roles') ? $user->getRelation('roles') : $user->roles();
        $slugs = [];

        if (!is_array($roles)) {
            return [];
        }

        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                continue;
            }

            $slug = (string) $role->getAttribute('slug');

            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    private function findActiveUserByEmail(string $email): ?User
    {
        return User::query()
            ->with('roles')
            ->where('email', '=', $this->normalizeEmail($email))
            ->where('status', '=', 1)
            ->first();
    }

    private function isRememberExpired(User $user): bool
    {
        $expiresAt = (string) $user->getAttribute('remember_expires_at');

        if ($expiresAt === '') {
            return true;
        }

        return strtotime($expiresAt) !== false && strtotime($expiresAt) < time();
    }

    private function isPasswordResetExpired(PasswordReset $reset): bool
    {
        $expiresAt = (string) $reset->getAttribute('expires_at');

        if ($expiresAt === '') {
            return true;
        }

        return strtotime($expiresAt) !== false && strtotime($expiresAt) < time();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function adminRole(): string
    {
        return (string) $this->config->get('auth.defaults.admin_role', 'admin');
    }

    private function defaultRole(): string
    {
        return (string) $this->config->get('auth.defaults.default_role', 'user');
    }

    private function sessionKey(): string
    {
        return (string) $this->config->get('auth.session.user_key', 'auth_user_id');
    }

    private function intendedKey(): string
    {
        return (string) $this->config->get('auth.session.intended_key', 'auth_intended_url');
    }

    private function rememberCookieName(): string
    {
        return (string) $this->config->get('auth.remember.cookie', 'marwa_auth_remember');
    }

    private function rememberTtl(): int
    {
        return max(60, (int) $this->config->get('auth.remember.ttl', 2592000));
    }

    private function passwordResetTtl(): int
    {
        return max(300, (int) $this->config->get('auth.password_reset.ttl', 3600));
    }

    private function resetPasswordUrl(string $token): string
    {
        return '/auth/reset-password/' . rawurlencode($token);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function formatDateTime(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
