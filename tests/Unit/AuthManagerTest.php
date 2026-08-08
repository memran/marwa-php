<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Contracts\AdminActorInterface;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Contracts\AdminUserProviderInterface;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\NullAdminUserProvider;
use App\Modules\Auth\Support\TwoFactorAuth;
use App\Modules\Auth\Support\TwoFactorAuthService;
use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class AuthManagerTest extends TestCase
{
    private const RFC_KEY = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-auth-static-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/sessions', 0777, true);

        ini_set('session.save_path', $this->basePath . '/sessions');

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nTIMEZONE=UTC\nADMIN_BOOTSTRAP_EMAIL=admin@marwa.test\nADMIN_BOOTSTRAP_PASSWORD=ExampleAdminPassword123!\n"
        );
        file_put_contents(
            $this->basePath . '/config/cache.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'enabled' => true,\n    'driver' => 'file',\n    'buffered' => false,\n    'file' => [\n        'path' => '" . addslashes($this->basePath . '/cache') . "',\n    ],\n];\n"
        );

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        foreach ([
            $this->basePath . '/.env',
            $this->basePath . '/config/cache.php',
        ] as $file) {
            @unlink($file);
        }

        $this->removeDirectory($this->basePath . '/cache');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/sessions');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_ENV['TIMEZONE'],
            $_ENV['ADMIN_BOOTSTRAP_EMAIL'],
            $_ENV['ADMIN_BOOTSTRAP_PASSWORD'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE'],
            $_SERVER['ADMIN_BOOTSTRAP_EMAIL'],
            $_SERVER['ADMIN_BOOTSTRAP_PASSWORD']
        );

        parent::tearDown();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_dir($file)) {
                $this->removeDirectory($file);
                continue;
            }

            @unlink($file);
        }

        @rmdir($path);
    }

    public function testStaticLoginUsesBootstrapCredentialsWithoutDatabase(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->add(AdminUserProviderInterface::class, new NullAdminUserProvider());

        $auth = $app->make(AuthManager::class);

        self::assertTrue($auth->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));
        self::assertTrue($auth->check());

        $user = $auth->user();

        self::assertInstanceOf(AdminActorInterface::class, $user);
        self::assertSame('admin@marwa.test', $user->getAttribute('email'));
        self::assertSame('Administrator', $user->getAttribute('name'));
        self::assertFalse($auth->attempt('admin@marwa.test', 'wrong-password'));
        $auth->logout();
        self::assertFalse($auth->check());
        self::assertFalse($auth->resetPassword('token', 'new-password'));
    }

    public function testBootstrapLoginRequiresExplicitCredentials(): void
    {
        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nTIMEZONE=UTC\n"
        );

        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->add(AdminUserProviderInterface::class, new NullAdminUserProvider());

        $auth = $app->make(AuthManager::class);

        self::assertFalse($auth->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));
        self::assertFalse($auth->check());
    }

    public function testLoginAttemptsAreRateLimitedBeforePasswordVerification(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->add(AdminUserProviderInterface::class, new NullAdminUserProvider());

        $auth = $app->make(AuthManager::class);

        for ($i = 0; $i < 5; $i++) {
            self::assertFalse($auth->attempt('admin@marwa.test', 'wrong-password', '127.0.0.1'));
        }

        self::assertFalse($auth->attempt('admin@marwa.test', 'ExampleAdminPassword123!', '127.0.0.1'));
        self::assertSame('rate_limited', $auth->lastFailureReason());
        self::assertFalse($auth->check());
    }

    public function testLogoutRegeneratesSessionId(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->add(AdminUserProviderInterface::class, new NullAdminUserProvider());

        $auth = $app->make(AuthManager::class);

        self::assertTrue($auth->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));
        $sessionIdBeforeLogout = session()->id();

        $auth->logout();
        $sessionIdAfterLogout = session()->id();

        self::assertNotSame($sessionIdBeforeLogout, $sessionIdAfterLogout);
        self::assertFalse($auth->check());
    }

    public function testPersistedUserWithTwoFactorMustVerifyBeforeSessionStarts(): void
    {
        $app = $this->createTwoFactorAwareApp(TwoFactorAuth::MODE_OPTIONAL);
        $totp = new TwoFactorAuthService();
        $auth = $app->make(AuthManager::class);
        $secret = self::RFC_KEY;

        self::assertTrue($auth->attempt('persisted@marwa.test', 'SecretPass123!'));
        self::assertFalse($auth->check());
        self::assertTrue($auth->twoFactorChallengePending());
        self::assertSame('persisted@marwa.test', $auth->twoFactorEmail());
        self::assertSame($secret, $auth->twoFactorSecret());
        self::assertFalse($auth->twoFactorEnrolling());
        self::assertFalse($auth->completeTwoFactor('000000'));

        self::assertTrue($auth->completeTwoFactor($totp->currentCode($secret)));
        self::assertTrue($auth->check());
        self::assertFalse($auth->twoFactorChallengePending());
    }

    public function testDisabledModeSkipsTwoFactorChallengeEntirely(): void
    {
        $app = $this->createTwoFactorAwareApp(TwoFactorAuth::MODE_DISABLED);

        $auth = $app->make(AuthManager::class);

        self::assertTrue($auth->attempt('persisted@marwa.test', 'SecretPass123!'));
        self::assertTrue($auth->check());
        self::assertFalse($auth->twoFactorChallengePending());
    }

    public function testRequiredModeForcesEnrolmentChallengeForPlainPersistedUser(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->add(
            AdminUserProviderInterface::class,
            new TwoFactorAwareUserProvider(
                new TwoFactorAdminUser(
                    'plain@marwa.test',
                    'Plain User',
                    password_hash('SecretPass123!', PASSWORD_DEFAULT),
                    null,
                    null
                )
            )
        );
        $app->add(
            TwoFactorAuth::class,
            new TwoFactorAuth(new TwoFactorAuthService(), static fn (): string => TwoFactorAuth::MODE_REQUIRED)
        );

        $auth = $app->make(AuthManager::class);

        self::assertTrue($auth->attempt('plain@marwa.test', 'SecretPass123!'));
        self::assertFalse($auth->check());
        self::assertTrue($auth->twoFactorChallengePending());
        self::assertTrue($auth->twoFactorEnrolling());
        self::assertNotNull($auth->twoFactorSecret());
    }

    private function createTwoFactorAwareApp(string $mode): Application
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->add(
            AdminUserProviderInterface::class,
            new TwoFactorAwareUserProvider(
                new TwoFactorAdminUser(
                    'persisted@marwa.test',
                    'Persisted User',
                    password_hash('SecretPass123!', PASSWORD_DEFAULT),
                    self::RFC_KEY,
                    '2026-08-08 00:00:00'
                )
            )
        );
        $app->add(
            TwoFactorAuth::class,
            new TwoFactorAuth(new TwoFactorAuthService(), static fn (): string => $mode)
        );

        return $app;
    }
}

final class TwoFactorAwareUserProvider implements AdminUserProviderInterface
{
    public function __construct(private readonly ?AdminAuthenticatableInterface $user = null)
    {
    }

    public function findPersistedUserByEmail(string $email): ?AdminAuthenticatableInterface
    {
        if ($this->user === null) {
            return null;
        }

        return strtolower((string) $this->user->getAttribute('email')) === strtolower($email)
            ? $this->user
            : null;
    }

    public function findPersistedUserById(int $id): ?AdminAuthenticatableInterface
    {
        return null;
    }

    public function createBootstrapUser(string $name, string $email): AdminAuthenticatableInterface
    {
        return new TwoFactorAdminUser($email, $name, null, null, null);
    }
}

final class TwoFactorAdminUser implements AdminAuthenticatableInterface
{
    public function __construct(
        private readonly string $email,
        private readonly string $name,
        private readonly ?string $passwordHash,
        private ?string $secret,
        private ?string $enabledAt,
    ) {
    }

    public function getAttribute(string $key): mixed
    {
        return match ($key) {
            'email' => $this->email,
            'name' => $this->name,
            'password' => $this->passwordHash,
            'two_factor_secret' => $this->secret,
            'two_factor_enabled_at' => $this->enabledAt,
            default => null,
        };
    }

    public function getId(): int
    {
        return 1;
    }

    public function role(): ?Role
    {
        return null;
    }

    public function hasPermission(string $permission): bool
    {
        return true;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function recordSuccessfulLogin(string $timestamp): void
    {
    }

    public function updatePasswordHash(string $hash): void
    {
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->enabledAt !== null;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->secret;
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->secret = $secret;
        $this->enabledAt = date('Y-m-d H:i:s');
    }

    public function disableTwoFactor(): void
    {
        $this->secret = null;
        $this->enabledAt = null;
    }
}
