<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsRepository;
use Marwa\DB\Schema\Schema;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class SettingsRepositoryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-settings-repository-' . bin2hex(random_bytes(6));

        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nTIMEZONE=UTC\nDB_ENABLED=1\nDB_CONNECTION=sqlite\nDB_DATABASE={$this->basePath}/database/database.sqlite\n"
        );

        file_put_contents(
            $this->basePath . '/config/database.php',
            <<<PHP
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => '{$this->basePath}/database/database.sqlite',
            'debug' => false,
        ],
    ],
    'debug' => false,
    'useDebugPanel' => false,
];
PHP
        );

        file_put_contents($this->basePath . '/config/event.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'listeners' => [],\n    'subscribers' => [],\n];\n");
        file_put_contents($this->basePath . '/database/database.sqlite', '');
    }

    protected function tearDown(): void
    {
        @restore_error_handler();
        @restore_exception_handler();

        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/config/event.php',
            $this->basePath . '/database/database.sqlite',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/database');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_ENV['TIMEZONE'],
            $_ENV['DB_ENABLED'],
            $_ENV['DB_CONNECTION'],
            $_ENV['DB_DATABASE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE'],
            $_SERVER['DB_ENABLED'],
            $_SERVER['DB_CONNECTION'],
            $_SERVER['DB_DATABASE']
        );

        parent::tearDown();
    }

    public function testRepositoryPersistsAndReadsSettingsUsingTheOrmModel(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        Schema::create('settings', function ($table): void {
            $table->bigIncrements('id');
            $table->string('category', 64);
            $table->string('setting_key', 120);
            $table->text('setting_value')->nullable();
            $table->timestamps();
            $table->unique(['category', 'setting_key'], 'settings_category_key_unique');
        });

        $repository = new SettingsRepository();
        $catalog = new SettingsCatalog();

        $values = $catalog->defaults();
        $values['app']['name'] = 'Ops Console';
        $values['system']['pagination_limit'] = 25;
        $values['email']['from_email'] = 'alerts@example.test';

        $repository->save($catalog->flattenForStorage($values));

        $stored = $repository->all();

        self::assertSame('Ops Console', $stored['app.name']);
        self::assertSame('25', $stored['system.pagination_limit']);
        self::assertSame('alerts@example.test', $stored['email.from_email']);
    }
}
