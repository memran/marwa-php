<?php

declare(strict_types=1);

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
        $rows = $catalog->flattenForStorage($catalog->defaults());
        $statement = db()->getPdo()->prepare(
            'INSERT INTO settings (category, setting_key, setting_value, created_at, updated_at) VALUES (:category, :setting_key, :setting_value, :created_at, :updated_at)'
        );
        $timestamp = gmdate('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $statement->execute([
                ':category' => $row['category'],
                ':setting_key' => $row['key'],
                ':setting_value' => $row['value'],
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        Schema::drop('settings');
    }
};
