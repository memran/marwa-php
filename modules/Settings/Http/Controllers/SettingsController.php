<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsStore;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsStore $store,
        private readonly SettingsCatalog $catalog,
    ) {}

    public function index(): ResponseInterface
    {
        $this->ensureViewNamespace();

        return $this->view('@settings/index', [
            'categories' => $this->catalog->categories(),
            'settings' => $this->store->all(),
            'notice' => session('settings.notice'),
            'errors' => session('settings.errors', []),
        ]);
    }

    public function update(): ResponseInterface
    {
        $this->ensureViewNamespace();

        $submitted = request('settings', []);
        $normalized = is_array($submitted) ? $this->catalog->normalizeSubmission($submitted, $this->store->all()) : null;

        if ($normalized === null || $normalized['errors'] !== []) {
            session()->flash('settings.errors', $normalized['errors'] ?? [
                '_global' => ['Settings payload is invalid.'],
            ]);

            return $this->redirect('/admin/settings');
        }

        $this->store->update($normalized['values']);
        session()->flash('settings.notice', 'Settings updated successfully.');

        return $this->redirect('/admin/settings');
    }

    private function ensureViewNamespace(): void
    {
        if (!app()->has(View::class)) {
            return;
        }

        app()->view()->addNamespace('settings', dirname(__DIR__, 2) . '/resources/views');
    }
}
