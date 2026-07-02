<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Modules\Settings\Support\SettingsActivityLogger;
use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsLogoStorage;
use App\Modules\Settings\Support\SettingsMaintenance;
use App\Modules\Settings\Support\SettingsStore;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsStore $store,
        private readonly SettingsCatalog $catalog,
        private readonly SettingsLogoStorage $logoStorage,
        private readonly SettingsMaintenance $maintenance,
        private readonly SettingsActivityLogger $activity,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->view('@settings/index', [
            'categories' => $this->catalog->categories(),
            'settings' => $this->store->all(),
            'errors' => session('settings.errors', []),
        ]);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $before = $this->store->all();
        $body = $request->getParsedBody();
        $submitted = is_array($body) ? ($body['settings'] ?? []) : [];

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
            $this->logoStorage->remove();
            $submitted['ui']['logo_url'] = '';
        } else {
            $upload = $this->logoStorage->uploadedLogo($request);

            if ($upload !== null) {
                try {
                    $submitted['ui']['logo_url'] = $this->logoStorage->store($upload);
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
        $this->activity->settingsUpdated($before, $normalized['values']);
        session()->flash('settings.notice', 'Settings updated successfully.');

        return $this->redirect('/admin/settings');
    }

    public function purgeCache(): ResponseInterface
    {
        try {
            $this->maintenance->purgeCache();
            $this->activity->cacheCleared();
            session()->flash('settings.notice', 'Cache cleared successfully.');
        } catch (\Throwable $e) {
            session()->flash('settings.notice', 'Failed to clear cache: ' . $e->getMessage());
        }

        return $this->redirect('/admin/settings');
    }

    public function clearLogs(): ResponseInterface
    {
        try {
            $count = $this->maintenance->clearLogs();

            if ($count > 0) {
                $this->activity->logsCleared($count);
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
