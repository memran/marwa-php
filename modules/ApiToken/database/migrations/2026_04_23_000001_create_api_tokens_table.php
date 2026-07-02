<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
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

        PermissionMigrationHelper::removePermissions([
            'api_token.view',
            'api_token.create',
            'api_token.revoke',
        ]);
    }

    private function insertPermissions(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View API tokens', 'slug' => 'api_token.view', 'group' => 'api_token'],
            ['name' => 'Create API tokens', 'slug' => 'api_token.create', 'group' => 'api_token'],
            ['name' => 'Revoke API tokens', 'slug' => 'api_token.revoke', 'group' => 'api_token'],
        ]);
    }
};
