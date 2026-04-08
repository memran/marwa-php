<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ControllerThemeSplitTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-controller-theme-' . bin2hex(random_bytes(6));

        $this->makeDirectory($this->basePath);
        $this->makeDirectory($this->basePath . '/bootstrap/cache');
        $this->makeDirectory($this->basePath . '/config');
        $this->makeDirectory($this->basePath . '/routes');
        $this->makeDirectory($this->basePath . '/resources/views/components');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/home');
        $this->makeDirectory($this->basePath . '/resources/views/themes/admin/views/dashboard');

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nFRONTEND_THEME=default\nADMIN_THEME=admin\nTIMEZONE=UTC\n"
        );

        file_put_contents(
            $this->basePath . '/routes/web.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin'], static function ($routes): void {
    $routes->get('/', [DashboardController::class, 'index'])->name('admin.dashboard')->register();
});
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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{% block title %}Frontend{% endblock %}</title>
</head>
<body class="theme theme--{{ _theme_name }}">
    <main class="theme-shell theme-shell--frontend">
        {% block content %}{% endblock %}
    </main>
</body>
</html>
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/welcome.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block title %}Frontend Home{% endblock %}

{% block content %}
<section data-theme="{{ _theme_name }}">
    Frontend theme: {{ _theme_name }}
</section>
{% endblock %}
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/home/index.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block title %}Frontend Home{% endblock %}

{% block content %}
<section data-theme="{{ _theme_name }}">
    Frontend theme: {{ _theme_name }}
</section>
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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{% block title %}Admin{% endblock %}</title>
</head>
<body class="theme theme--{{ _theme_name }}">
    <main class="theme-shell theme-shell--admin">
        {% block content %}{% endblock %}
    </main>
</body>
</html>
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/admin/views/dashboard/index.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block title %}Admin Dashboard{% endblock %}

{% block content %}
<section data-theme="{{ _theme_name }}">
    Admin theme: {{ _theme_name }}
</section>
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

    public function testFrontendAndBackendControllersUseDifferentThemesWithoutBleed(): void
    {
        $this->bootApplication();

        $home = new HomeController();
        $dashboard = new DashboardController();

        $frontend = $home->index();
        $backend = $dashboard->index();
        $frontendAgain = $home->index();

        self::assertInstanceOf(ResponseInterface::class, $frontend);
        self::assertInstanceOf(ResponseInterface::class, $backend);
        self::assertStringContainsString('Frontend theme: default', (string) $frontend->getBody());
        self::assertStringContainsString('Admin theme: admin', (string) $backend->getBody());
        self::assertStringContainsString('Frontend theme: default', (string) $frontendAgain->getBody());
    }

    public function testKernelDispatchesPublicAndAdminRoutesThroughTheirRespectiveControllers(): void
    {
        $this->bootApplication();

        /** @var Application $app */
        $app = $GLOBALS['marwa_app'];
        $kernel = $app->make(HttpKernel::class);

        $homeResponse = $kernel->handle(new ServerRequest(uri: '/', method: 'GET'));
        $adminResponse = $kernel->handle(new ServerRequest(uri: '/admin', method: 'GET'));

        self::assertSame(200, $homeResponse->getStatusCode());
        self::assertSame(200, $adminResponse->getStatusCode());
        self::assertStringContainsString('Frontend theme: default', (string) $homeResponse->getBody());
        self::assertStringContainsString('Admin theme: admin', (string) $adminResponse->getBody());
    }

    public function testModuleViewsCanExtendTheActiveThemeLayout(): void
    {
        $this->makeDirectory($this->basePath . '/modules/Blog/resources/views');
        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'paths' => ['{$this->basePath}/modules'],
    'cache' => '{$this->basePath}/bootstrap/cache/modules.php',
];
PHP
        );
        file_put_contents(
            $this->basePath . '/modules/Blog/manifest.php',
            <<<'PHP'
<?php

return [
    'name' => 'Blog',
    'slug' => 'blog',
    'paths' => [
        'views' => 'resources/views',
    ],
];
PHP
        );
        file_put_contents(
            $this->basePath . '/modules/Blog/resources/views/post.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block content %}
Module view inside {{ _theme_name }} theme.
{% endblock %}
TWIG
        );

        $this->bootApplication();

        /** @var Application $app */
        $app = $GLOBALS['marwa_app'];

        $rendered = $app->view()->render('@blog/post.twig');

        self::assertStringContainsString('theme--default', $app->view()->render('home/index'));
        self::assertStringContainsString('theme--default', $rendered);
        self::assertStringContainsString('Module view inside default theme.', $rendered);
    }

    private function bootApplication(): void
    {
        $GLOBALS['marwa_app'] = new Application($this->basePath);
        $GLOBALS['marwa_app']->make(AppBootstrapper::class)->bootstrap();
    }

    private function makeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($path);
    }
}
