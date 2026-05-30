<?php

declare(strict_types=1);

use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Support\SettingsCatalog;
use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('settings', function ($table): void {
            $table->bigIncrements('id');
            $table->string('category', 64);
            $table->string('setting_key', 120);
            $table->text('setting_value')->nullable();
            $table->timestamps();
            $table->unique(['category', 'setting_key'], 'settings_category_key_unique');
        });

        $catalog = new SettingsCatalog();
        foreach ($catalog->flattenForStorage($catalog->defaults()) as $row) {
            Setting::updateOrCreate(
                [
                    'category' => $row['category'],
                    'setting_key' => $row['key'],
                ],
                [
                    'setting_value' => $row['value'],
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::drop('settings');
    }
};
