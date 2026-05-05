<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Supports\Runtime;
use Marwa\Router\Http\Input;
use PHPUnit\Framework\TestCase;

final class SecurityRiskReportTest extends TestCase
{
    private string $basePath;
    private string $logPath;
    private ?string $originalLogContents = null;
    private ?string $createdEmail = null;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        unset($GLOBALS['marwa_app']);
        Input::reset();

        $this->basePath = dirname(__DIR__, 2);
        $this->logPath = $this->basePath . '/storage/security/risk.jsonl';

        if (is_file($this->logPath)) {
            $this->originalLogContents = (string) file_get_contents($this->logPath);
        }

        $this->makeDirectory(dirname($this->logPath));
        file_put_contents(
            $this->logPath,
            implode(PHP_EOL, [
                json_encode([
                    'timestamp' => gmdate(DATE_ATOM, time() - 7200),
                    'category' => 'csrf',
                    'message' => 'CSRF token mismatch detected.',
                    'score' => 90,
                    'context' => [
                        'method' => 'POST',
                        'path' => '/admin/users',
                    ],
                ], JSON_THROW_ON_ERROR),
                json_encode([
                    'timestamp' => gmdate(DATE_ATOM, time() - 3600),
                    'category' => 'auth',
                    'message' => 'Repeated failed admin login attempt.',
                    'score' => 60,
                    'context' => [
                        'email' => 'admin@example.test',
                    ],
                ], JSON_THROW_ON_ERROR),
            ]) . PHP_EOL
        );
    }

    protected function tearDown(): void
    {
        Runtime::setConsoleOverride(null);
        unset($GLOBALS['marwa_app']);

        if ($this->originalLogContents !== null) {
            file_put_contents($this->logPath, $this->originalLogContents);
        } else {
            @unlink($this->logPath);
        }

        if ($this->createdEmail !== null) {
            try {
                User::findByEmail($this->createdEmail)?->delete();
            } catch (\Throwable) {
            }
        }

        parent::tearDown();
    }

    public function test_risk_report_page_displays_menu_and_data(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        $adminRole = Role::findBySlug('admin');
        self::assertInstanceOf(Role::class, $adminRole);

        $this->createdEmail = 'security-risk-' . bin2hex(random_bytes(4)) . '@marwa.test';

        $user = User::create([
            'name' => 'Security Risk Admin',
            'email' => $this->createdEmail,
            'password' => password_hash('SecurityRiskPassword123!', PASSWORD_DEFAULT),
            'role_id' => (int) $adminRole->getKey(),
            'is_active' => true,
        ]);

        self::assertInstanceOf(User::class, $user);

        session()->set('admin_authenticated', true);
        session()->set('admin_user_name', 'Security Risk Admin');
        session()->set('admin_user_email', $this->createdEmail);

        $kernel = $app->make(HttpKernel::class);
        $response = $kernel->handle(new ServerRequest(uri: '/admin/security/risk', method: 'GET'));

        self::assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        self::assertStringContainsString('Risk report and signal data.', $body);
        self::assertStringContainsString('2 total signals', $body);
        self::assertStringContainsString('CSRF token mismatch detected.', $body);
        self::assertStringContainsString('Repeated failed admin login attempt.', $body);
        self::assertStringContainsString('&quot;method&quot;: &quot;POST&quot;', $body);
        self::assertStringContainsString('&quot;path&quot;: &quot;/admin/users&quot;', $body);
        self::assertStringContainsString('/admin/security/risk', $body);
        self::assertStringContainsString('Risk Report', $body);
    }

    private function makeDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0777, true);
    }
}
