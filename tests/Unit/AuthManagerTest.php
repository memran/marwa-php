<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class AuthManagerTest extends TestCase
{
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
        ] as $file) {
            @unlink($file);
        }

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

    public function testStaticLoginUsesBootstrapCredentialsWithoutDatabase(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;

        $auth = new AuthManager();

        self::assertTrue($auth->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));
        self::assertTrue($auth->check());

        $user = $auth->user();

        self::assertInstanceOf(User::class, $user);
        self::assertSame('admin@marwa.test', $user->getAttribute('email'));
        self::assertSame('Administrator', $user->getAttribute('name'));
        self::assertFalse($auth->attempt('admin@marwa.test', 'wrong-password'));
        $auth->logout();
        self::assertFalse($auth->check());
        self::assertFalse($auth->resetPassword('token', 'new-password'));
    }
}
