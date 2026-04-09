<?php

declare(strict_types=1);

namespace Tests\Feature;

use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use PHPUnit\Framework\TestCase;

final class StarterThemeRoutingTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-starter-' . bin2hex(random_bytes(6));

        $this->makeDirectory($this->basePath);
        $this->makeDirectory($this->basePath . '/config');
        $this->makeDirectory($this->basePath . '/routes');
        $this->makeDirectory($this->basePath . '/resources/views/components');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/home');
        $this->makeDirectory($this->basePath . '/resources/views/themes/admin/views/dashboard');

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_NAME=\"Marwa Starter\"\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nFRONTEND_THEME=default\nADMIN_THEME=admin\nTIMEZONE=UTC\n"
        );

        file_put_contents(
            $this->basePath . '/routes/web.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class]], static function ($routes): void {
    $routes->get('/', [DashboardController::class, 'index'])->name('admin.dashboard')->register();
});
PHP
        );

        file_put_contents(
            $this->basePath . '/config/app.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
];
PHP
        );

        file_put_contents(
            $this->basePath . '/routes/api.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/health', static fn (): \Psr\Http\Message\ResponseInterface => Response::json([
    'status' => 'ok',
    'app' => config('app.name', 'MarwaPHP'),
]))->name('health')->register();
PHP
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/manifest.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => 'default',
    'assets_url' => '/themes/default',
    'views_path' => 'views',
];
PHP
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/layout.twig',
            <<<'TWIG'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{% block title %}Frontend{% endblock %}</title>
</head>
<body class="theme theme--{{ _theme_name }}">
    {% block content %}{% endblock %}
</body>
</html>
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/home/index.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block content %}
<section>Frontend theme: {{ _theme_name }}</section>
{% endblock %}
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/admin/manifest.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => 'admin',
    'parent' => 'default',
    'assets_url' => '/themes/admin',
    'views_path' => 'views',
];
PHP
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/admin/views/layout.twig',
            <<<'TWIG'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{% block title %}Admin{% endblock %}</title>
</head>
<body class="theme theme--{{ _theme_name }}">
    {% block content %}{% endblock %}
</body>
</html>
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/admin/views/dashboard/index.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block content %}
<section>Admin theme: {{ _theme_name }}</section>
{% endblock %}
TWIG
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        @restore_error_handler();
        @restore_exception_handler();
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function testFrontendAndAdminRoutesUseTheirConfiguredThemes(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $kernel = $app->make(HttpKernel::class);

        $frontend = $kernel->handle(new ServerRequest(uri: '/', method: 'GET'));
        $admin = $kernel->handle(new ServerRequest(uri: '/admin', method: 'GET'));
        $frontendAgain = $kernel->handle(new ServerRequest(uri: '/', method: 'GET'));
        $health = $kernel->handle(new ServerRequest(uri: '/health', method: 'GET'));

        self::assertSame(200, $frontend->getStatusCode());
        self::assertSame(200, $admin->getStatusCode());
        self::assertSame(200, $frontendAgain->getStatusCode());
        self::assertSame(200, $health->getStatusCode());
        self::assertStringContainsString('Frontend theme: default', (string) $frontend->getBody());
        self::assertStringContainsString('Admin theme: admin', (string) $admin->getBody());
        self::assertStringContainsString('Frontend theme: default', (string) $frontendAgain->getBody());
        self::assertStringContainsString('Marwa Starter', (string) $health->getBody());
    }

    private function makeDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0777, true);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();

            if ($item->isDir()) {
                @rmdir($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
