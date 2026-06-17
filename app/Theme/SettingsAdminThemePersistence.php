<?php

declare(strict_types=1);

namespace App\Theme;

use App\Modules\Settings\Support\SettingsStore;

final class SettingsAdminThemePersistence implements AdminThemePersistence
{
    public function __construct(
        private readonly SettingsStore $store
    ) {
    }

    public function publish(string $themeName): void
    {
        $values = $this->store->all();
        $values['ui'] = is_array($values['ui'] ?? null) ? $values['ui'] : [];
        $values['ui']['admin_theme'] = $themeName;

        $this->store->update($values);
    }
}
