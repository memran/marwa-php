<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Supports\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class AuthManagerTest extends TestCase
{
    public function testRememberTokenHelpersRoundTrip(): void
    {
        $cookie = AuthManager::encodeRememberToken('selector123', 'validator456');

        self::assertSame([
            'selector' => 'selector123',
            'validator' => 'validator456',
        ], AuthManager::decodeRememberToken($cookie));
        self::assertNull(AuthManager::decodeRememberToken('invalid-token'));
    }

    public function testCookieHeaderIncludesSecurityFlags(): void
    {
        $header = AuthManager::buildCookieHeader('remember', 'token-value', 3600, [
            'path' => '/auth',
            'secure' => true,
            'sameSite' => 'Lax',
        ]);

        self::assertStringContainsString('remember=token-value', $header);
        self::assertStringContainsString('Max-Age=3600', $header);
        self::assertStringContainsString('Path=/auth', $header);
        self::assertStringContainsString('HttpOnly', $header);
        self::assertStringContainsString('SameSite=Lax', $header);
        self::assertStringContainsString('Secure', $header);
    }

    public function testIntendedUrlRoundTripUsesTheSession(): void
    {
        $configDir = $this->createConfigDir();

        try {
            $config = new Config($configDir);
            $manager = new AuthManager($config, new ArraySession());

            $manager->setIntendedUrl('/admin/users');

            self::assertSame('/admin/users', $manager->consumeIntendedUrl('/admin'));
            self::assertSame('/admin', $manager->consumeIntendedUrl('/admin'));
        } finally {
            $this->deleteConfigDir($configDir);
        }
    }

    private function createConfigDir(): string
    {
        $dir = sys_get_temp_dir() . '/marwa-auth-config-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);

        file_put_contents($dir . '/auth.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    'module' => [
        'enabled' => true,
    ],
    'defaults' => [
        'admin_role' => 'admin',
        'default_role' => 'user',
    ],
    'session' => [
        'user_key' => 'auth_user_id',
        'intended_key' => 'auth_intended_url',
    ],
    'remember' => [
        'cookie' => 'marwa_auth_remember',
        'ttl' => 3600,
    ],
    'password_reset' => [
        'ttl' => 3600,
    ],
    'seed' => [
        'admin_name' => 'Administrator',
        'admin_email' => 'admin@example.test',
        'admin_password' => 'ChangeMe123!',
    ],
];
PHP);

        return $dir;
    }

    private function deleteConfigDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
