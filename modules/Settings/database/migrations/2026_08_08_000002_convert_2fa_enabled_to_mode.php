<?php

declare(strict_types=1);

use App\Modules\Settings\Models\Setting;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        $rows = Setting::query()
            ->where('category', 'security')
            ->where('setting_key', '2fa_enabled')
            ->get();

        foreach ($rows as $row) {
            $enabled = ((int) $row->getAttribute('setting_value')) === 1;

            Setting::updateOrCreate(
                [
                    'category' => 'security',
                    'setting_key' => '2fa_mode',
                ],
                [
                    'setting_value' => $enabled ? 'required' : 'disabled',
                ]
            );
        }
    }

    public function down(): void
    {
        $rows = Setting::query()
            ->where('category', 'security')
            ->where('setting_key', '2fa_mode')
            ->get();

        foreach ($rows as $row) {
            $enabled = (string) $row->getAttribute('setting_value') === 'required';

            Setting::updateOrCreate(
                [
                    'category' => 'security',
                    'setting_key' => '2fa_enabled',
                ],
                [
                    'setting_value' => $enabled ? '1' : '0',
                ]
            );
        }
    }
};