<?php

declare(strict_types=1);

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('api_tokens', function ($table): void {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('token_hash');
            $table->string('token_prefix', 8);
            $table->json('allowed_ips')->nullable();
            $table->integer('rate_limit')->default(60);
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->unsigned()->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->insertPermissions();
    }

    public function down(): void
    {
        Schema::drop('api_tokens');
    }

    private function insertPermissions(): void
    {
        $permissions = [
            ['name' => 'View API tokens', 'slug' => 'api_token.view'],
            ['name' => 'Create API tokens', 'slug' => 'api_token.create'],
            ['name' => 'Revoke API tokens', 'slug' => 'api_token.revoke'],
        ];

        $timestamp = gmdate('Y-m-d H:i:s');

        foreach ($permissions as $permission) {
            db()->getPdo()->prepare(
                'INSERT OR IGNORE INTO permissions (name, slug, created_at, updated_at) VALUES (:name, :slug, :created_at, :updated_at)'
            )->execute([
                ':name' => $permission['name'],
                ':slug' => $permission['slug'],
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
        }
    }
};