<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsStore;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

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
            'notice' => session('settings.notice'),
            'errors' => session('settings.errors', []),
        ]);
    }

    public function update(): ResponseInterface
    {
        $before = $this->store->all();
        $submitted = request('settings', []);
        $normalized = is_array($submitted) ? $this->catalog->normalizeSubmission($submitted, $this->store->all()) : null;

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
