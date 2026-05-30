<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Settings\Http\Controllers\SettingsController;
use Laminas\Diactoros\UploadedFile;
use Marwa\Framework\Application;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Psr\Http\Message\ServerRequestInterface;

final class SettingsControllerTest extends TestCase
{
    public function testStoreLogoUploadMovesFileIntoPublicUploads(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-settings-logo-' . bin2hex(random_bytes(6));
        $this->makeDirectory($basePath . '/public');

        $previousApp = $GLOBALS['marwa_app'] ?? null;
        $GLOBALS['marwa_app'] = new Application($basePath);

        $source = tempnam(sys_get_temp_dir(), 'marwa-logo-');
        self::assertNotFalse($source);
        file_put_contents($source, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" fill="#2563eb"/></svg>');

        try {
            $controller = (new ReflectionClass(SettingsController::class))->newInstanceWithoutConstructor();
            $method = new \ReflectionMethod(SettingsController::class, 'storeLogoUpload');
            $method->setAccessible(true);

            $relativePath = $method->invoke(
                $controller,
                new UploadedFile($source, filesize($source), UPLOAD_ERR_OK, 'logo.svg', 'image/svg+xml')
            );

            self::assertSame('uploads/settings/interface/logo.svg', $relativePath);
            self::assertFileExists(public_path($relativePath));
        } finally {
            unset($GLOBALS['marwa_app']);

            if ($previousApp instanceof Application) {
                $GLOBALS['marwa_app'] = $previousApp;
            }

            $this->deleteDirectory($basePath);

            if (is_file($source)) {
                @unlink($source);
            }
        }
    }

    public function testRemoveStoredLogoDeletesUploadedLogoFiles(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-settings-logo-remove-' . bin2hex(random_bytes(6));
        $logoDirectory = $basePath . '/public/uploads/settings/interface';
        $this->makeDirectory($logoDirectory);

        $previousApp = $GLOBALS['marwa_app'] ?? null;
        $GLOBALS['marwa_app'] = new Application($basePath);

        file_put_contents($logoDirectory . '/logo.png', 'png');
        file_put_contents($logoDirectory . '/logo.svg', 'svg');

        try {
            $controller = (new ReflectionClass(SettingsController::class))->newInstanceWithoutConstructor();
            $method = new \ReflectionMethod(SettingsController::class, 'removeStoredLogo');
            $method->setAccessible(true);
            $method->invoke($controller);

            self::assertFileDoesNotExist($logoDirectory . '/logo.png');
            self::assertFileDoesNotExist($logoDirectory . '/logo.svg');
        } finally {
            unset($GLOBALS['marwa_app']);

            if ($previousApp instanceof Application) {
                $GLOBALS['marwa_app'] = $previousApp;
            }

            $this->deleteDirectory($basePath);
        }
    }

    public function testUploadedLogoIgnoresNoFileUploads(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-settings-logo-request-' . bin2hex(random_bytes(6));
        $this->makeDirectory($basePath);

        $previousApp = $GLOBALS['marwa_app'] ?? null;
        $app = new Application($basePath);

        $controller = (new ReflectionClass(SettingsController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SettingsController::class, 'uploadedLogo');
        $method->setAccessible(true);
        $app->add(ServerRequestInterface::class, RequestFactory::fromArrays(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/settings'],
            [],
            [],
            [],
            []
        ));

        try {
            self::assertNull($method->invoke($controller));
        } finally {
            unset($GLOBALS['marwa_app']);

            if ($previousApp instanceof Application) {
                $GLOBALS['marwa_app'] = $previousApp;
            }

            $this->deleteDirectory($basePath);
        }
    }

    private function makeDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0777, true);
    }

    private function deleteDirectory(string $path): void
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
