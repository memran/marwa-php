<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Views\View;
use Marwa\Logger\SimpleLogger;
use Marwa\Logger\Storage\StorageFactory;
use Marwa\Logger\Support\SensitiveDataFilter;

final class SettingsApplier
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param array<string, array<string, mixed>> $values
     */
    public function apply(array $values): void
    {
        $items = $this->config->all();
        $items['settings'] = $values;
        $items['app'] = $items['app'] ?? [];
        $items['view'] = $items['view'] ?? [];
        $items['mail'] = $items['mail'] ?? [];
        $items['cache'] = $items['cache'] ?? [];
        $items['logger'] = $items['logger'] ?? [];
        $items['security'] = $items['security'] ?? [];

        $items['app']['name'] = $values['app']['name'] ?? ($items['app']['name'] ?? 'MarwaPHP');
        $items['app']['debug'] = (bool) ($values['app']['debug'] ?? false);
        $items['app']['env'] = $values['app']['env'] ?? env('APP_ENV', 'production');
        $items['app']['timezone'] = $values['app']['timezone'] ?? env('TIMEZONE', 'UTC');
        $items['app']['locale'] = $values['app']['locale'] ?? 'en';
        $items['app']['maintenance_mode'] = (bool) ($values['app']['maintenance_mode'] ?? false);

        $items['view']['activeTheme'] = $values['ui']['theme'] ?? ($items['view']['activeTheme'] ?? 'default');
        $items['view']['adminTheme'] = $values['ui']['admin_theme'] ?? ($items['view']['adminTheme'] ?? 'admin');
        $items['mail']['smtp']['host'] = $values['email']['smtp_host'] ?? ($items['mail']['smtp']['host'] ?? '127.0.0.1');
        $items['mail']['smtp']['port'] = $values['email']['smtp_port'] ?? ($items['mail']['smtp']['port'] ?? 1025);
        $items['mail']['smtp']['username'] = $values['email']['smtp_user'] ?? ($items['mail']['smtp']['username'] ?? null);
        $items['mail']['smtp']['password'] = $values['email']['smtp_pass'] ?? ($items['mail']['smtp']['password'] ?? null);
        $items['mail']['from']['address'] = $values['email']['from_email'] ?? ($items['mail']['from']['address'] ?? 'no-reply@example.com');
        $items['cache']['enabled'] = (bool) ($values['cache']['enabled'] ?? ($items['cache']['enabled'] ?? true));
        $items['cache']['driver'] = $values['cache']['driver'] ?? ($items['cache']['driver'] ?? 'memory');
        $items['logger']['enable'] = (bool) ($values['logging']['enabled'] ?? ($items['logger']['enable'] ?? false));
        $items['logger']['level'] = $values['logging']['level'] ?? ($items['logger']['level'] ?? ($items['logger']['storage']['level'] ?? 'debug'));
        $items['logger']['storage']['level'] = $items['logger']['level'];
        $items['logger']['storage']['retention_days'] = (int) ($values['logging']['retention_days'] ?? ($items['logger']['storage']['retention_days'] ?? 30));
        $items['security']['trustedOrigins'] = $values['api']['allowed_origins'] ?? ($items['security']['trustedOrigins'] ?? []);
        $items['pagination']['default_per_page'] = (int) ($values['system']['pagination_limit'] ?? 10);
        $items['system']['max_upload_size'] = $values['system']['max_upload_size'] ?? ($items['system']['max_upload_size'] ?? '10M');
        $items['system']['date_format'] = $values['system']['date_format'] ?? ($items['system']['date_format'] ?? 'Y-m-d');
        $items['system']['time_format'] = $values['system']['time_format'] ?? ($items['system']['time_format'] ?? 'H:i');
        $items['security']['password_policy'] = $values['security']['password_policy'] ?? ($items['security']['password_policy'] ?? '');
        $items['security']['login_attempt_limit'] = (int) ($values['security']['login_attempt_limit'] ?? ($items['security']['login_attempt_limit'] ?? 5));
        $items['security']['2fa_enabled'] = (bool) ($values['security']['2fa_enabled'] ?? ($items['security']['2fa_enabled'] ?? false));
        $items['error']['appName'] = $items['app']['name'];
        $items['error']['environment'] = $items['app']['env'];
        $items['view']['debug'] = $items['app']['debug'];
        $items['settings']['lifecycle']['pagination']['default_per_page'] = $items['pagination']['default_per_page'];
        $items['settings']['lifecycle']['system']['pagination_limit'] = $items['pagination']['default_per_page'];
        $items['settings']['lifecycle']['system']['max_upload_size'] = $items['system']['max_upload_size'];
        $items['settings']['lifecycle']['system']['date_format'] = $items['system']['date_format'];
        $items['settings']['lifecycle']['system']['time_format'] = $items['system']['time_format'];
        $items['settings']['lifecycle']['app']['name'] = $items['app']['name'];
        $items['settings']['lifecycle']['app']['env'] = $items['app']['env'];
        $items['settings']['lifecycle']['app']['debug'] = $items['app']['debug'];
        $items['settings']['lifecycle']['app']['timezone'] = $items['app']['timezone'];
        $items['settings']['lifecycle']['app']['maintenance_mode'] = $items['app']['maintenance_mode'];
        $items['settings']['lifecycle']['security']['password_policy'] = $items['security']['password_policy'];
        $items['settings']['lifecycle']['security']['login_attempt_limit'] = $items['security']['login_attempt_limit'];
        $items['settings']['lifecycle']['security']['two_factor_enabled'] = $items['security']['2fa_enabled'];
        $items['settings']['lifecycle']['security']['trusted_origins'] = $items['security']['trustedOrigins'];
        $items['settings']['lifecycle']['theme']['frontend'] = $items['view']['activeTheme'];
        $items['settings']['lifecycle']['theme']['admin'] = $items['view']['adminTheme'];
        $items['settings']['lifecycle']['logging']['enabled'] = $items['logger']['enable'];
        $items['settings']['lifecycle']['logging']['level'] = $items['logger']['level'];
        $items['settings']['lifecycle']['logging']['retention_days'] = $items['logger']['storage']['retention_days'];
        $items['settings']['lifecycle']['cache']['enabled'] = $items['cache']['enabled'];
        $items['settings']['lifecycle']['cache']['driver'] = $items['cache']['driver'];

        $this->config->prime($items);

        $this->syncLogger($items);

        if (isset($items['app']['timezone']) && is_string($items['app']['timezone']) && $items['app']['timezone'] !== '') {
            $tz = @date_default_timezone_set($items['app']['timezone']);
            if ($tz === false) {
                error_clear_last();
            }
        }

        if (app()->has(View::class)) {
            $view = app()->view();
            $view->share('_settings', $values);
            $view->share('_system_date_format', $items['system']['date_format']);
            $view->share('_system_time_format', $items['system']['time_format']);
            $view->share('_system_max_upload_size', $items['system']['max_upload_size']);
            $view->share('_security_password_policy', $items['security']['password_policy']);
            $view->share('_security_login_attempt_limit', $items['security']['login_attempt_limit']);
            $view->share('_security_two_factor_enabled', $items['security']['2fa_enabled']);
        }
    }

    /**
     * @param array<string, mixed> $items
     */
    private function syncLogger(array $items): void
    {
        if (!app()->has(LoggerAdapter::class)) {
            return;
        }

        $logger = app()->make(LoggerAdapter::class);

        if (!$logger instanceof SimpleLogger) {
            return;
        }

        $this->setLoggerProperty($logger, 'appName', (string) ($items['app']['name'] ?? 'MarwaPHP'));
        $this->setLoggerProperty($logger, 'env', (string) ($items['app']['env'] ?? env('APP_ENV', 'production')));
        $this->setLoggerProperty($logger, 'sink', StorageFactory::make($this->config->getArray('logger.storage', [])));
        $this->setLoggerProperty(
            $logger,
            'filter',
            new SensitiveDataFilter($this->config->getArray('logger.filter', []))
        );
        $this->setLoggerProperty($logger, 'logging', (bool) ($items['logger']['enable'] ?? false));
        $this->setLoggerProperty($logger, 'productionMinLevel', (string) ($items['logger']['level'] ?? 'debug'));
    }

    private function setLoggerProperty(SimpleLogger $logger, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($logger, $property);
        $reflection->setValue($logger, $value);
    }
}
