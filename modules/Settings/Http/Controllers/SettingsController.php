<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsStore;
use Marwa\Framework\Controllers\Controller;
use Marwa\Support\File;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsStore $store,
        private readonly SettingsCatalog $catalog,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->view('@settings/index', [
            'categories' => $this->catalog->categories(),
            'settings' => $this->store->all(),
            'errors' => session('settings.errors', []),
        ]);
    }

    public function update(): ResponseInterface
    {
        $before = $this->store->all();
        $submitted = request('settings', []);

        if (!is_array($submitted)) {
            session()->flash('settings.errors', [
                '_global' => ['Settings payload is invalid.'],
            ]);

            return $this->redirect('/admin/settings');
        }

        $submitted = array_replace_recursive($before, $submitted);
        $removeLogo = (bool) ($submitted['ui']['remove_logo'] ?? false);
        unset($submitted['ui']['remove_logo']);

        if ($removeLogo) {
            $this->removeStoredLogo();
            $submitted['ui']['logo_url'] = '';
        } else {
            $upload = $this->uploadedLogo();

            if ($upload instanceof UploadedFileInterface) {
                try {
                    $submitted['ui']['logo_url'] = $this->storeLogoUpload($upload);
                } catch (\Throwable $exception) {
                    session()->flash('settings.errors', [
                        'ui.logo_url' => [$exception->getMessage()],
                    ]);

                    return $this->redirect('/admin/settings');
                }
            }
        }

        $normalized = $this->catalog->normalizeSubmission($submitted, $before);

        if ($normalized === null || $normalized['errors'] !== []) {
            session()->flash('settings.errors', $normalized['errors'] ?? [
                '_global' => ['Settings payload is invalid.'],
            ]);

            return $this->redirect('/admin/settings');
        }

        $this->store->update($normalized['values']);
        if ($before !== $normalized['values']) {
            app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                'settings.updated',
                'Updated settings.',
                app(\App\Modules\Auth\Support\AuthManager::class)->user(),
                'settings',
                null,
                ['before' => $before, 'after' => $normalized['values']]
            );
        }
        session()->flash('settings.notice', 'Settings updated successfully.');

        return $this->redirect('/admin/settings');
    }

    private function uploadedLogo(): ?UploadedFileInterface
    {
        $request = request();

        if (!is_object($request) || !method_exists($request, 'getUploadedFiles')) {
            return null;
        }

        $upload = $this->nestedUploadedFile($request->getUploadedFiles(), ['settings_logo_url']);

        if (!$upload instanceof UploadedFileInterface) {
            return null;
        }

        if (method_exists($upload, 'getError') && $upload->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $upload;
    }

    /**
     * @param array<int|string, mixed> $files
     * @param list<string> $path
     */
    private function nestedUploadedFile(array $files, array $path): mixed
    {
        $current = $files;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function storeLogoUpload(UploadedFileInterface $upload): string
    {
        if ($upload->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('The uploaded logo could not be read.');
        }

        $clientName = (string) $upload->getClientFilename();
        $extension = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Upload a PNG, JPG, WebP, GIF, or SVG logo.');
        }

        $clientMediaType = strtolower((string) $upload->getClientMediaType());
        if ($clientMediaType !== '' && !str_starts_with($clientMediaType, 'image/') && $extension !== 'svg') {
            throw new \RuntimeException('Upload a valid image file for the logo.');
        }

        $directory = public_path('uploads/settings/interface');
        File::makeDirectory($directory);

        $relativePath = 'uploads/settings/interface/logo.' . $extension;
        $destination = public_path($relativePath);

        foreach (glob($directory . DIRECTORY_SEPARATOR . 'logo.*') ?: [] as $existingFile) {
            if (is_file($existingFile) && basename($existingFile) !== basename($destination)) {
                File::delete($existingFile);
            }
        }

        if (is_file($destination)) {
            File::delete($destination);
        }

        $upload->moveTo($destination);

        return $relativePath;
    }

    private function removeStoredLogo(): void
    {
        $directory = public_path('uploads/settings/interface');

        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . 'logo.*') ?: [] as $existingFile) {
            if (is_file($existingFile)) {
                File::delete($existingFile);
            }
        }
    }

    public function purgeCache(): ResponseInterface
    {
        try {
            if (app()->has(\Marwa\Framework\Contracts\CacheInterface::class)) {
                app()->cache()->flush();
                app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                    'settings.cache_cleared',
                    'Cleared settings cache.',
                    app(\App\Modules\Auth\Support\AuthManager::class)->user(),
                    'settings',
                    null,
                    ['state' => ['cache' => 'flushed']]
                );
                session()->flash('settings.notice', 'Cache cleared successfully.');
            } else {
                session()->flash('settings.notice', 'Cache service not available.');
            }
        } catch (\Throwable $e) {
            session()->flash('settings.notice', 'Failed to clear cache: ' . $e->getMessage());
        }

        return $this->redirect('/admin/settings');
    }

    public function clearLogs(): ResponseInterface
    {
        try {
            $logsPath = logs_path();
            if (!is_dir($logsPath)) {
                session()->flash('settings.notice', 'Logs directory not found.');
                return $this->redirect('/admin/settings');
            }

            $files = glob($logsPath . DIRECTORY_SEPARATOR . '*.log');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $count++;
                }
            }

            if ($count > 0) {
                app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                    'settings.logs_cleared',
                    'Cleared log files.',
                    app(\App\Modules\Auth\Support\AuthManager::class)->user(),
                    'settings',
                    null,
                    ['state' => ['deleted_files' => $count]]
                );
                session()->flash('settings.notice', "Deleted {$count} log file(s).");
            } else {
                session()->flash('settings.notice', 'No log files to delete.');
            }
        } catch (\Throwable $e) {
            session()->flash('settings.notice', 'Failed to clear logs: ' . $e->getMessage());
        }

        return $this->redirect('/admin/settings');
    }
}
