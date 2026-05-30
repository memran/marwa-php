<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use App\Modules\Settings\Models\Setting;
use Marwa\DB\Connection\ConnectionManager;

final class SettingsRepository
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        try {
            $rows = Setting::query()
                ->select('category', 'setting_key', 'setting_value')
                ->orderBy('category')
                ->orderBy('setting_key')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $values = [];

        foreach ($rows as $row) {
            $category = trim((string) $row->getAttribute('category'));
            $key = trim((string) $row->getAttribute('setting_key'));

            if ($category === '' || $key === '') {
                continue;
            }

            $values[$category . '.' . $key] = (string) $row->getAttribute('setting_value');
        }

        return $values;
    }

    /**
     * @param list<array{category:string,key:string,value:string}> $rows
     */
    public function save(array $rows): void
    {
        app(ConnectionManager::class)->transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                Setting::updateOrCreate(
                    [
                        'category' => (string) $row['category'],
                        'setting_key' => (string) $row['key'],
                    ],
                    [
                        'setting_value' => (string) $row['value'],
                    ]
                );
            }
        });
    }
}
