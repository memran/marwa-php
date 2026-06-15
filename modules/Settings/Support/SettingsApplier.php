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
        $items['logger']['storage'] = $items['logger']['storage'] ?? [];
        $items['security'] = $items['security'] ?? [];
        $items['pagination'] = $items['pagination'] ?? [];
        $items['system'] = $items['system'] ?? [];

        $appName = (string) ($values['app']['name'] ?? ($items['app']['name'] ?? 'MarwaPHP'));
        $appEnv = (string) ($values['app']['env'] ?? env('APP_ENV', 'production'));
        $appDebug = (bool) ($values['app']['debug'] ?? ($items['app']['debug'] ?? false));
        $appTimezone = (string) ($values['app']['timezone'] ?? env('TIMEZONE', 'UTC'));
        $appLocale = (string) ($values['app']['locale'] ?? ($items['app']['locale'] ?? 'en'));
        $maintenanceMode = (bool) ($values['app']['maintenance_mode'] ?? ($items['app']['maintenance_mode'] ?? false));
        $themeFrontend = (string) ($values['ui']['theme'] ?? ($items['view']['activeTheme'] ?? 'default'));
        $themeAdmin = (string) ($values['ui']['admin_theme'] ?? ($items['view']['adminTheme'] ?? 'executive'));
        $logoUrl = (string) ($values['ui']['logo_url'] ?? ($items['ui']['logo_url'] ?? ''));
        $smtpHost = (string) ($values['email']['smtp_host'] ?? ($items['mail']['smtp']['host'] ?? '127.0.0.1'));
        $smtpPort = (int) ($values['email']['smtp_port'] ?? ($items['mail']['smtp']['port'] ?? 1025));
        $smtpUser = $values['email']['smtp_user'] ?? ($items['mail']['smtp']['username'] ?? null);
        $smtpPass = $values['email']['smtp_pass'] ?? ($items['mail']['smtp']['password'] ?? null);
        $fromEmail = (string) ($values['email']['from_email'] ?? ($items['mail']['from']['address'] ?? 'no-reply@example.com'));
        $fromName = (string) ($values['email']['from_name'] ?? ($items['mail']['from']['name'] ?? 'MarwaPHP'));
        $cacheEnabled = (bool) ($values['cache']['enabled'] ?? ($items['cache']['enabled'] ?? true));
        $cacheDriver = (string) ($values['cache']['driver'] ?? ($items['cache']['driver'] ?? 'memory'));
        $logEnabled = (bool) ($values['logging']['enabled'] ?? ($items['logger']['enable'] ?? false));
        $logLevel = (string) ($values['logging']['level'] ?? ($items['logger']['level'] ?? ($items['logger']['storage']['level'] ?? 'debug')));
        $logRetentionDays = (int) ($values['logging']['retention_days'] ?? ($items['logger']['storage']['retention_days'] ?? 30));
        $paginationLimit = (int) ($values['system']['pagination_limit'] ?? ($items['pagination']['default_per_page'] ?? 10));
        $maxUploadSize = (string) ($values['system']['max_upload_size'] ?? ($items['system']['max_upload_size'] ?? '10M'));
        $dateFormat = (string) ($values['system']['date_format'] ?? ($items['system']['date_format'] ?? 'Y-m-d'));
        $timeFormat = (string) ($values['system']['time_format'] ?? ($items['system']['time_format'] ?? 'H:i'));
        $passwordPolicy = (string) ($values['security']['password_policy'] ?? ($items['security']['password_policy'] ?? ''));
        $loginAttemptLimit = (int) ($values['security']['login_attempt_limit'] ?? ($items['security']['login_attempt_limit'] ?? 5));
        $twoFactorEnabled = (bool) ($values['security']['2fa_enabled'] ?? ($items['security']['2fa_enabled'] ?? false));

        $items['app'] = [
            'name' => $appName,
            'debug' => $appDebug,
            'env' => $appEnv,
            'timezone' => $appTimezone,
            'locale' => $appLocale,
            'maintenance_mode' => $maintenanceMode,
        ];
        $items['view']['activeTheme'] = $themeFrontend;
        $items['view']['adminTheme'] = $themeAdmin;
        $items['view']['debug'] = $appDebug;
        $items['ui']['logo_url'] = $logoUrl;
        $items['mail']['smtp']['host'] = $smtpHost;
        $items['mail']['smtp']['port'] = $smtpPort;
        $items['mail']['smtp']['username'] = $smtpUser;
        $items['mail']['smtp']['password'] = $smtpPass;
        $items['mail']['from']['address'] = $fromEmail;
        $items['mail']['from']['name'] = $fromName;
        $items['cache']['enabled'] = $cacheEnabled;
        $items['cache']['driver'] = $cacheDriver;
        $items['logger']['enable'] = $logEnabled;
        $items['logger']['level'] = $logLevel;
        $items['logger']['storage']['level'] = $logLevel;
        $items['logger']['storage']['retention_days'] = $logRetentionDays;
        $items['pagination']['default_per_page'] = $paginationLimit;
        $items['system']['max_upload_size'] = $maxUploadSize;
        $items['system']['date_format'] = $dateFormat;
        $items['system']['time_format'] = $timeFormat;
        $items['security']['password_policy'] = $passwordPolicy;
        $items['security']['login_attempt_limit'] = $loginAttemptLimit;
        $items['security']['2fa_enabled'] = $twoFactorEnabled;
        $items['error']['appName'] = $appName;
        $items['error']['environment'] = $appEnv;
        $items['settings']['lifecycle'] = [
            'pagination' => [
                'default_per_page' => $paginationLimit,
            ],
            'system' => [
                'pagination_limit' => $paginationLimit,
                'max_upload_size' => $maxUploadSize,
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
            ],
            'app' => [
                'name' => $appName,
                'env' => $appEnv,
                'debug' => $appDebug,
                'timezone' => $appTimezone,
                'maintenance_mode' => $maintenanceMode,
            ],
            'security' => [
                'password_policy' => $passwordPolicy,
                'login_attempt_limit' => $loginAttemptLimit,
                'two_factor_enabled' => $twoFactorEnabled,
            ],
            'theme' => [
                'frontend' => $themeFrontend,
                'admin' => $themeAdmin,
            ],
            'ui' => [
                'logo_url' => $logoUrl,
            ],
            'logging' => [
                'enabled' => $logEnabled,
                'level' => $logLevel,
                'retention_days' => $logRetentionDays,
            ],
            'cache' => [
                'enabled' => $cacheEnabled,
                'driver' => $cacheDriver,
            ],
        ];

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
