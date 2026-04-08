<?php

declare(strict_types=1);

namespace Tests\Feature;

use Marwa\ErrorHandler\Contracts\DebugReporterInterface;
use Marwa\ErrorHandler\ErrorHandler;
use Marwa\DebugBar\Collectors\DbQueryCollector;
use Marwa\DebugBar\Collectors\MemoryCollector;
use Marwa\DebugBar\Collectors\RequestCollector;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\DebugBar\DebugBar;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class InfrastructureServicesTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryRoots = [];
    private bool $handlersBooted = false;

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryRoots) as $root) {
            $this->removeDirectory($root);
        }

        unset($GLOBALS['marwa_app']);
        if ($this->handlersBooted) {
            @restore_error_handler();
            @restore_exception_handler();
        }

        parent::tearDown();
    }

    public function testDebugbarCollectorsAndLoggerConfigAreResolvedFromEnv(): void
    {
        $app = $this->createApplication([
            'APP_DEBUG' => 'true',
            'APP_ENV' => 'testing',
            'APP_NAME' => 'Marwa Test App',
            'ERROR_ENABLED' => 'true',
            'ERROR_USE_LOGGER' => 'true',
            'ERROR_USE_DEBUG_REPORTER' => 'true',
            'LOG_ENABLE' => 'true',
            'LOG_CHANNEL' => 'file',
            'LOG_LEVEL' => 'notice',
            'LOG_PREFIX' => 'marwa-php',
        ]);

        $config = $app->make(AppBootstrapper::class)->bootstrap();
        $errorHandler = $app->make(ErrorHandlerAdapter::class)->handler();
        $this->handlersBooted = $errorHandler instanceof ErrorHandler;

        self::assertTrue($config['debugbar']);
        self::assertSame([
            RequestCollector::class,
            DbQueryCollector::class,
            MemoryCollector::class,
            \Marwa\DebugBar\Collectors\LogCollector::class,
            \Marwa\DebugBar\Collectors\SessionCollector::class,
            \Marwa\DebugBar\Collectors\PhpCollector::class,
            \Marwa\DebugBar\Collectors\TimelineCollector::class,
            \Marwa\DebugBar\Collectors\VarDumperCollector::class,
            \Marwa\DebugBar\Collectors\ExceptionCollector::class,
            \Marwa\DebugBar\Collectors\CacheCollector::class,
            \Marwa\DebugBar\Collectors\KpiCollector::class,
        ], $config['collectors']);
        self::assertSame('Marwa Test App', config('error.appName'));
        self::assertSame('testing', config('error.environment'));
        self::assertSame('notice', config('logger.storage.level'));
        self::assertSame('marwa-php', config('logger.storage.prefix'));
        self::assertSame($this->currentRoot() . '/storage/logs', config('logger.storage.path'));
        self::assertSame(['password', 'token', 'authorization', 'cookie', 'secret'], config('logger.filter'));
        self::assertInstanceOf(LoggerInterface::class, $app->make(LoggerInterface::class));
        self::assertInstanceOf(ErrorHandler::class, $errorHandler);
        self::assertSame($app->make(LoggerInterface::class), $this->readProperty($errorHandler, 'logger'));
        self::assertInstanceOf(DebugBar::class, $app->make('debugbar'));
        self::assertInstanceOf(DebugReporterInterface::class, $this->readProperty($errorHandler, 'debugReporter'));
    }

    public function testDebugbarServiceIsRegisteredOnlyWhenDebugIsEnabled(): void
    {
        $app = $this->createApplication([
            'APP_DEBUG' => 'true',
            'APP_ENV' => 'testing',
            'ERROR_ENABLED' => 'true',
            'ERROR_USE_LOGGER' => 'true',
            'ERROR_USE_DEBUG_REPORTER' => 'true',
            'LOG_ENABLE' => 'true',
        ]);

        $config = $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = $app->make(ErrorHandlerAdapter::class)->handler() instanceof ErrorHandler;

        self::assertTrue($config['debugbar']);
        self::assertInstanceOf(DebugBar::class, debugger());
        self::assertInstanceOf(DebugBar::class, $app->make('debugbar'));
        self::assertSame([
            RequestCollector::class,
            DbQueryCollector::class,
            MemoryCollector::class,
            \Marwa\DebugBar\Collectors\LogCollector::class,
            \Marwa\DebugBar\Collectors\SessionCollector::class,
            \Marwa\DebugBar\Collectors\PhpCollector::class,
            \Marwa\DebugBar\Collectors\TimelineCollector::class,
            \Marwa\DebugBar\Collectors\VarDumperCollector::class,
            \Marwa\DebugBar\Collectors\ExceptionCollector::class,
            \Marwa\DebugBar\Collectors\CacheCollector::class,
            \Marwa\DebugBar\Collectors\KpiCollector::class,
        ], $config['collectors']);
    }

    public function testDebugbarAndErrorReporterStayDisabledWhenDebugIsOff(): void
    {
        $app = $this->createApplication([
            'APP_DEBUG' => 'false',
            'APP_ENV' => 'production',
            'ERROR_ENABLED' => 'true',
            'ERROR_USE_LOGGER' => 'true',
            'ERROR_USE_DEBUG_REPORTER' => 'true',
            'LOG_ENABLE' => 'false',
        ]);

        $app->make(AppBootstrapper::class)->bootstrap();
        $handler = $app->make(ErrorHandlerAdapter::class)->handler();
        $this->handlersBooted = $handler instanceof ErrorHandler;

        self::assertFalse(config('app.debugbar'));
        self::assertNull(debugger());
        self::assertNull($this->readProperty($handler, 'debugReporter'));
    }

    public function testErrorHandlerCanBeDisabledFromEnv(): void
    {
        $app = $this->createApplication([
            'APP_DEBUG' => 'false',
            'APP_ENV' => 'production',
            'ERROR_ENABLED' => 'false',
            'ERROR_USE_LOGGER' => 'true',
            'ERROR_USE_DEBUG_REPORTER' => 'true',
        ]);

        $app->make(AppBootstrapper::class)->bootstrap();

        self::assertNull($app->make(ErrorHandlerAdapter::class)->handler());
    }

    /**
     * @param array<string, string> $env
     */
    private function createApplication(array $env): Application
    {
        $root = sys_get_temp_dir() . '/marwa-infra-' . bin2hex(random_bytes(6));
        $this->temporaryRoots[] = $root;

        unset($GLOBALS['marwa_app']);
        $this->makeDirectory($root);
        $this->makeDirectory($root . '/config');
        $this->makeDirectory($root . '/routes');
        $this->makeDirectory($root . '/resources/views/components');
        $this->makeDirectory($root . '/storage/logs');

        $env = array_merge([
            'APP_NAME' => 'MarwaPHP',
            'APP_TITLE' => 'MarwaPHP',
            'APP_URL' => 'http://localhost/',
            'APP_KEY' => '',
            'TIMEZONE' => 'UTC',
            'MAINTENANCE' => 'false',
            'MAINTENANCE_TIME' => '300',
            'FRONTEND_THEME' => 'default',
            'ADMIN_THEME' => 'admin',
            'LOG_ENABLE' => 'true',
            'LOG_CHANNEL' => 'file',
            'LOG_LEVEL' => 'debug',
            'LOG_PREFIX' => 'marwa-php',
            'ERROR_ENABLED' => 'true',
            'ERROR_USE_LOGGER' => 'true',
            'ERROR_USE_DEBUG_REPORTER' => 'true',
        ], $env);

        file_put_contents($root . '/.env', $this->buildEnv($env));
        file_put_contents($root . '/config/app.php', $this->appConfigStub());
        file_put_contents($root . '/config/logger.php', $this->loggerConfigStub());
        file_put_contents($root . '/config/error.php', $this->errorConfigStub());

        $GLOBALS['marwa_app'] = new Application($root);

        return $GLOBALS['marwa_app'];
    }

    /**
     * @return non-empty-string
     */
    private function currentRoot(): string
    {
        return end($this->temporaryRoots) ?: '';
    }

    /**
     * @param array<string, string> $env
     * @return non-empty-string
     */
    private function buildEnv(array $env): string
    {
        $lines = [];

        foreach ($env as $key => $value) {
            $lines[] = $key . '="' . addcslashes($value, "\\\"") . '"';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return non-empty-string
     */
    private function appConfigStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'title' => env('APP_TITLE', env('APP_NAME', 'MarwaPHP')),
    'base_path' => env('APP_URL', 'http://localhost/'),
    'debug' => env('APP_DEBUG', false),
    'debugbar' => env('APP_DEBUG', false),
    'collectors' => [
        Marwa\DebugBar\Collectors\RequestCollector::class,
        Marwa\DebugBar\Collectors\DbQueryCollector::class,
        Marwa\DebugBar\Collectors\MemoryCollector::class,
        Marwa\DebugBar\Collectors\LogCollector::class,
        Marwa\DebugBar\Collectors\SessionCollector::class,
        Marwa\DebugBar\Collectors\PhpCollector::class,
        Marwa\DebugBar\Collectors\TimelineCollector::class,
        Marwa\DebugBar\Collectors\VarDumperCollector::class,
        Marwa\DebugBar\Collectors\ExceptionCollector::class,
        Marwa\DebugBar\Collectors\CacheCollector::class,
        Marwa\DebugBar\Collectors\KpiCollector::class,
    ],
    'key' => env('APP_KEY', generate_key()),
    'defaultLocale' => 'en',
    'langPath' => resources_path() . DIRECTORY_SEPARATOR . 'lang',
    'providers' => [
        Marwa\Framework\Providers\KernalServiceProvider::class,
    ],
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\SessionMiddleware::class,
        Marwa\Framework\Middlewares\MaintenanceMiddleware::class,
        Marwa\Framework\Middlewares\SecurityMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class,
    ],
    'maintenance' => env('MAINTENANCE', env('MAINTAINANCE', false)),
    'maintenance_time' => env('MAINTENANCE_TIME', 300),
];
PHP;
    }

    /**
     * @return non-empty-string
     */
    private function loggerConfigStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'enable' => env('LOG_ENABLE', env('APP_ENV', 'production') !== 'production'),
    'filter' => [
        'password',
        'token',
        'authorization',
        'cookie',
        'secret',
    ],
    'storage' => [
        'driver' => env('LOG_CHANNEL', 'file'),
        'path' => storage_path('logs'),
        'prefix' => env('LOG_PREFIX', 'marwa-php'),
        'max_bytes' => '10MB',
        'level' => env('LOG_LEVEL', 'debug'),
    ],
];
PHP;
    }

    /**
     * @return non-empty-string
     */
    private function errorConfigStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Marwa\ErrorHandler\Support\FallbackRenderer;

return [
    'enabled' => env('ERROR_ENABLED', true),
    'appName' => env('APP_NAME', 'MarwaPHP'),
    'environment' => env('APP_ENV', 'production'),
    'useLogger' => env('ERROR_USE_LOGGER', true),
    'useDebugReporter' => env('ERROR_USE_DEBUG_REPORTER', true),
    'renderer' => FallbackRenderer::class,
];
PHP;
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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($path);
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);

        return $reflection->getValue($object);
    }
}
