<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use Marwa\Framework\Supports\Config;
use Marwa\Framework\Views\View;

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
        $items['mail']['smtp']['host'] = $values['email']['smtp_host'] ?? ($items['mail']['smtp']['host'] ?? '127.0.0.1');
        $items['mail']['smtp']['port'] = $values['email']['smtp_port'] ?? ($items['mail']['smtp']['port'] ?? 1025);
        $items['mail']['smtp']['username'] = $values['email']['smtp_user'] ?? ($items['mail']['smtp']['username'] ?? null);
        $items['mail']['smtp']['password'] = $values['email']['smtp_pass'] ?? ($items['mail']['smtp']['password'] ?? null);
        $items['mail']['from']['address'] = $values['email']['from_email'] ?? ($items['mail']['from']['address'] ?? 'no-reply@example.com');
        $items['cache']['driver'] = $values['cache']['driver'] ?? ($items['cache']['driver'] ?? 'memory');
        $items['logger']['storage']['level'] = $values['logging']['level'] ?? ($items['logger']['storage']['level'] ?? 'debug');
        $items['security']['trustedOrigins'] = $values['api']['allowed_origins'] ?? ($items['security']['trustedOrigins'] ?? []);

        $this->config->prime($items);

        if (isset($items['app']['timezone']) && is_string($items['app']['timezone']) && $items['app']['timezone'] !== '') {
            $tz = @date_default_timezone_set($items['app']['timezone']);
            if ($tz === false) {
                error_clear_last();
            }
        }

        if (app()->has(View::class)) {
            app()->view()->share('_settings', $values);
        }
    }
}
