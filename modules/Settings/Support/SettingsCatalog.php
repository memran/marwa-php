<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

final class SettingsCatalog
{
    private const THEME_TYPE_FRONT = 'front';
    private const THEME_TYPE_ADMIN = 'admin';

    /**
     * @return array<string, array{
     *     label:string,
     *     description:string,
     *     fields:array<string, array<string, mixed>>
     * }>
     */
    public function categories(): array
    {
        return [
            'business' => [
                'label' => 'Business Identity',
                'description' => 'Legal and contact details shown on invoices and customer documents.',
                'fields' => [
                    'name' => ['label' => 'Business name', 'input' => 'text', 'type' => 'string', 'default' => (string) env('APP_NAME', 'MarwaPHP')],
                    'address' => ['label' => 'Business address', 'input' => 'textarea', 'type' => 'string', 'default' => '', 'help' => 'Use multiple lines when needed.'],
                    'phone' => ['label' => 'Business phone', 'input' => 'text', 'type' => 'string', 'default' => ''],
                    'email' => ['label' => 'Business email', 'input' => 'email', 'type' => 'email', 'default' => (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com')],
                    'website' => ['label' => 'Website', 'input' => 'text', 'type' => 'string', 'default' => ''],
                    'tax_id' => ['label' => 'Tax / registration ID', 'input' => 'text', 'type' => 'string', 'default' => ''],
                ],
            ],
            'frontend' => [
                'label' => 'Public Website',
                'description' => 'Brand message and calls to action shown on the public ISP website.',
                'fields' => [
                    'eyebrow' => [
                        'label' => 'Hero eyebrow',
                        'input' => 'text',
                        'type' => 'string',
                        'default' => 'Reliable connectivity for every address',
                    ],
                    'headline' => [
                        'label' => 'Hero headline',
                        'input' => 'textarea',
                        'type' => 'string',
                        'default' => 'Internet that keeps your home and business moving.',
                    ],
                    'description' => [
                        'label' => 'Hero description',
                        'input' => 'textarea',
                        'type' => 'string',
                        'default' => 'Straightforward plans, responsive local support, and a network built for the way you work, learn, and connect.',
                    ],
                    'primary_cta_label' => [
                        'label' => 'Primary button label',
                        'input' => 'text',
                        'type' => 'string',
                        'default' => 'View internet plans',
                    ],
                    'primary_cta_url' => [
                        'label' => 'Primary button URL',
                        'input' => 'text',
                        'type' => 'url',
                        'default' => '#plans',
                    ],
                    'secondary_cta_label' => [
                        'label' => 'Secondary button label',
                        'input' => 'text',
                        'type' => 'string',
                        'default' => 'Customer portal',
                    ],
                    'secondary_cta_url' => [
                        'label' => 'Secondary button URL',
                        'input' => 'text',
                        'type' => 'url',
                        'default' => '/portal/login',
                    ],
                    'plans_title' => [
                        'label' => 'Plans section title',
                        'input' => 'text',
                        'type' => 'string',
                        'default' => 'Choose a plan built around your needs',
                    ],
                    'plans_description' => [
                        'label' => 'Plans section description',
                        'input' => 'textarea',
                        'type' => 'string',
                        'default' => 'Published prices and technical features are loaded directly from the active service catalog.',
                    ],
                    'hero_image_url' => [
                        'label' => 'Hero image URL',
                        'input' => 'media',
                        'type' => 'url',
                        'default' => '/assets/images/isp-network-hero.webp',
                        'help' => 'Use an absolute HTTP(S) URL or a public path beginning with /. Recommended ratio: 16:9.',
                        'media' => [
                            [
                                'name' => 'ISP network hero',
                                'value' => '/assets/images/isp-network-hero.webp',
                                'url' => '/assets/images/isp-network-hero.webp',
                            ],
                        ],
                    ],
                    'meta_description' => [
                        'label' => 'Search description',
                        'input' => 'textarea',
                        'type' => 'string',
                        'default' => 'Reliable internet service plans for homes and businesses, backed by responsive local support.',
                    ],
                ],
            ],
            'app' => [
                'label' => 'Application',
                'description' => 'Starter identity and global runtime defaults.',
                'fields' => [
                    'name' => ['label' => 'App name', 'input' => 'text', 'type' => 'string', 'default' => (string) env('APP_NAME', 'MarwaPHP'), 'help' => 'Applied to the runtime app config and shared labels immediately after save.'],
                    'env' => ['label' => 'Environment', 'input' => 'select', 'type' => 'string', 'default' => (string) env('APP_ENV', 'production'), 'options' => ['production' => 'production', 'staging' => 'staging', 'development' => 'development', 'testing' => 'testing', 'local' => 'local']],
                    'debug' => ['label' => 'Debug mode', 'input' => 'checkbox', 'type' => 'bool', 'default' => (bool) env('APP_DEBUG', false), 'help' => 'Mirrored into the runtime view and error config.'],
                    'timezone' => ['label' => 'Timezone', 'input' => 'select', 'type' => 'timezone', 'default' => (string) env('TIMEZONE', 'UTC'), 'options' => $this->timezoneOptions()],
                    'locale' => ['label' => 'Locale', 'input' => 'text', 'type' => 'string', 'default' => 'en'],
                    'maintenance_mode' => ['label' => 'Maintenance mode', 'input' => 'checkbox', 'type' => 'bool', 'default' => false, 'help' => 'Applied dynamically through the starter maintenance middleware.'],
                ],
            ],
            'system' => [
                'label' => 'System',
                'description' => 'Operational defaults used by admin and future starter modules.',
                'fields' => [
                    'pagination_limit' => ['label' => 'Pagination limit', 'input' => 'number', 'type' => 'int', 'default' => 10, 'min' => 1],
                    'max_upload_size' => ['label' => 'Max upload size', 'input' => 'text', 'type' => 'string', 'default' => '10M'],
                    'date_format' => ['label' => 'Date format', 'input' => 'text', 'type' => 'string', 'default' => 'Y-m-d'],
                    'time_format' => ['label' => 'Time format', 'input' => 'text', 'type' => 'string', 'default' => 'H:i'],
                ],
            ],
            'security' => [
                'label' => 'Security',
                'description' => 'Starter policy values that can be read globally by auth and security workflows.',
                'fields' => [
                    'password_policy' => ['label' => 'Password policy', 'input' => 'textarea', 'type' => 'string', 'default' => 'Minimum 8 characters, mixed case recommended.'],
                    'login_attempt_limit' => ['label' => 'Login attempt limit', 'input' => 'number', 'type' => 'int', 'default' => 5, 'min' => 1],
                    '2fa_mode' => [
                        'label' => 'Two-factor authentication',
                        'input' => 'select',
                        'type' => 'string',
                        'default' => 'disabled',
                        'options' => [
                            'disabled' => 'Disabled',
                            'optional' => 'Optional - users decide on their accounts',
                            'required' => 'Required - all sign-ins need TOTP',
                        ],
                        'help' => 'Uses Google Authenticator or FreeOTP. Required is enforced for every admin sign-in; Optional lets each user enable 2FA from their profile.',
                    ],
                ],
            ],
            'email' => [
                'label' => 'Email',
                'description' => 'SMTP delivery settings mirrored into the mail config at runtime.',
                'fields' => [
                    'smtp_host' => ['label' => 'SMTP host', 'input' => 'text', 'type' => 'string', 'default' => (string) env('MAIL_HOST', '127.0.0.1')],
                    'smtp_port' => ['label' => 'SMTP port', 'input' => 'number', 'type' => 'int', 'default' => (int) env('MAIL_PORT', 1025), 'min' => 1],
                    'smtp_user' => ['label' => 'SMTP user', 'input' => 'text', 'type' => 'string', 'default' => (string) env('MAIL_USERNAME', '')],
                    'smtp_pass' => ['label' => 'SMTP password', 'input' => 'password', 'type' => 'string', 'default' => (string) env('MAIL_PASSWORD', ''), 'sensitive' => true, 'help' => 'Leave blank to keep the current stored value.'],
                    'from_email' => ['label' => 'From email', 'input' => 'email', 'type' => 'email', 'default' => (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com')],
                    'from_name' => ['label' => 'From name', 'input' => 'text', 'type' => 'string', 'default' => (string) env('MAIL_FROM_NAME', 'MarwaPHP')],
                ],
            ],
            'ui' => [
                'label' => 'Interface',
                'description' => 'Visual and layout defaults shared across starter UIs.',
                'fields' => [
                    'theme' => ['label' => 'Frontend theme', 'input' => 'select', 'type' => 'string', 'default' => (string) env('FRONTEND_THEME', 'default'), 'options' => $this->themeOptions(self::THEME_TYPE_FRONT)],
                    'admin_theme' => ['label' => 'Admin theme', 'input' => 'select', 'type' => 'string', 'default' => (string) env('ADMIN_THEME', 'executive'), 'options' => $this->themeOptions(self::THEME_TYPE_ADMIN)],
                    'logo_url' => [
                        'label' => 'Logo upload',
                        'input' => 'file',
                        'type' => 'string',
                        'default' => '',
                        'accept' => 'image/*,.svg',
                        'help' => 'Upload a PNG, JPG, WebP, GIF, or SVG logo for the admin sidebar.',
                    ],
                ],
            ],
            'cache' => [
                'label' => 'Cache',
                'description' => 'Runtime cache preferences. Driver is mirrored into config for global reads.',
                'fields' => [
                    'enabled' => ['label' => 'Cache enabled', 'input' => 'checkbox', 'type' => 'bool', 'default' => true, 'help' => 'When disabled, caching is bypassed globally.'],
                    'driver' => ['label' => 'Cache driver', 'input' => 'select', 'type' => 'string', 'default' => extension_loaded('pdo_sqlite') ? 'sqlite' : 'memory', 'options' => ['memory' => 'memory', 'sqlite' => 'sqlite']],
                    'ttl' => ['label' => 'Default TTL (seconds)', 'input' => 'number', 'type' => 'int', 'default' => 3600, 'min' => 0],
                    'purge_cache' => ['label' => 'Purge cache', 'input' => 'action', 'type' => 'action', 'default' => false, 'help' => 'Clear all cached data.'],
                ],
            ],
            'logging' => [
                'label' => 'Logging',
                'description' => 'Application logging defaults mirrored into config where supported.',
                'fields' => [
                    'enabled' => ['label' => 'Logging enabled', 'input' => 'checkbox', 'type' => 'bool', 'default' => true, 'help' => 'When disabled, logging is bypassed globally.'],
                    'level' => ['label' => 'Log level', 'input' => 'select', 'type' => 'string', 'default' => (string) env('LOG_LEVEL', 'debug'), 'options' => ['debug' => 'debug', 'info' => 'info', 'notice' => 'notice', 'warning' => 'warning', 'error' => 'error', 'critical' => 'critical', 'alert' => 'alert', 'emergency' => 'emergency']],
                    'retention_days' => ['label' => 'Retention days', 'input' => 'number', 'type' => 'int', 'default' => 30, 'min' => 1],
                    'clear_logs' => ['label' => 'Clear logs', 'input' => 'action', 'type' => 'action', 'default' => false, 'help' => 'Delete all log files.'],
                ],
            ],
            'payment' => [
                'label' => 'Payment',
                'description' => 'Commercial defaults available globally to future billing flows.',
                'fields' => [
                    'currency' => ['label' => 'Currency', 'input' => 'select', 'type' => 'string', 'default' => 'USD', 'options' => [
                        'USD' => 'USD - US Dollar',
                        'EUR' => 'EUR - Euro',
                        'GBP' => 'GBP - British Pound',
                        'BDT' => 'BDT - Bangladeshi Taka',
                        'INR' => 'INR - Indian Rupee',
                        'AUD' => 'AUD - Australian Dollar',
                        'CAD' => 'CAD - Canadian Dollar',
                        'JPY' => 'JPY - Japanese Yen',
                        'CNY' => 'CNY - Chinese Yuan',
                        'SGD' => 'SGD - Singapore Dollar',
                        'MYR' => 'MYR - Malaysian Ringgit',
                        'THB' => 'THB - Thai Baht',
                        'VND' => 'VND - Vietnamese Dong',
                        'PHP' => 'PHP - Philippine Peso',
                        'IDR' => 'IDR - Indonesian Rupiah',
                    ]],
                    'tax_rate' => ['label' => 'Tax rate', 'input' => 'number', 'type' => 'float', 'default' => 5.0, 'min' => 0, 'step' => '0.10'],
                ],
            ],
            'billing' => [
                'label' => 'Billing',
                'description' => 'Customer code generation and billing defaults.',
                'fields' => [
                    'customer_code_format' => ['label' => 'Customer code format', 'input' => 'select', 'type' => 'string', 'default' => 'manual', 'options' => [
                        'manual' => 'Manual entry',
                        'sequential' => 'Sequential (auto-increment)',
                    ], 'help' => 'When sequential, customer codes are auto-generated as prefix + padded number.'],
                    'customer_code_prefix' => ['label' => 'Customer code prefix', 'input' => 'text', 'type' => 'string', 'default' => '', 'help' => 'Optional prefix prepended to sequential codes (e.g. "CUST-").'],
                    'customer_code_padding' => ['label' => 'Code number padding', 'input' => 'number', 'type' => 'int', 'default' => 5, 'min' => 2, 'max' => 12, 'help' => 'Zero-pad the sequential number to this many digits (e.g. 5 → 00001).'],
                ],
            ],
            'pop_billing' => [
                'label' => 'POP Billing',
                'description' => 'Controls when immutable POP manager billing reports are generated.',
                'fields' => [
                    'schedule_type' => [
                        'label' => 'Billing schedule',
                        'input' => 'select',
                        'type' => 'string',
                        'default' => 'monthly_day',
                        'options' => [
                            'interval_days' => 'Every number of days',
                            'monthly_day' => 'Fixed day of every month',
                        ],
                    ],
                    'interval_days' => [
                        'label' => 'Interval in days',
                        'input' => 'number',
                        'type' => 'int',
                        'default' => 10,
                        'min' => 1,
                        'max' => 366,
                        'help' => 'Used only for the interval schedule.',
                    ],
                    'interval_anchor_date' => [
                        'label' => 'Interval anchor date',
                        'input' => 'date',
                        'type' => 'string',
                        'default' => date('Y-01-01'),
                        'help' => 'Interval periods are calculated forward from this date.',
                    ],
                    'monthly_day' => [
                        'label' => 'Monthly billing day',
                        'input' => 'number',
                        'type' => 'int',
                        'default' => 5,
                        'min' => 1,
                        'max' => 28,
                        'help' => 'Reports cover the period from the previous billing day through the day before this billing day.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->categories() as $category => $meta) {
            foreach ($meta['fields'] as $key => $field) {
                $defaults[$category][$key] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, string>
     */
    private function timezoneOptions(): array
    {
        $options = [];

        foreach (timezone_identifiers_list() as $timezone) {
            $options[$timezone] = $timezone;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function themeOptions(string $expectedType): array
    {
        $options = [];
        $themesPath = $this->themePath();

        if (!is_dir($themesPath)) {
            return [
                'default' => 'default',
                'admin' => 'admin',
            ];
        }

        foreach (scandir($themesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $themeDir = $themesPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($themeDir)) {
                continue;
            }

            $manifest = $this->readThemeManifest($themeDir);
            if ($manifest === null) {
                continue;
            }

            $type = strtolower(trim((string) ($manifest['type'] ?? '')));
            if ($type !== $expectedType) {
                continue;
            }

            $name = trim((string) (($manifest['meta']['label'] ?? null) ?: ($manifest['name'] ?? $entry)));
            if ($name === '') {
                continue;
            }

            $options[$entry] = $name;
        }

        if ($options === []) {
            return $expectedType === self::THEME_TYPE_FRONT
                ? ['default' => 'default']
                : ['admin' => 'admin'];
        }

        ksort($options);

        return $options;
    }

    private function themePath(): string
    {
        try {
            $configuredPath = config('view.themePath');

            if (is_string($configuredPath) && trim($configuredPath) !== '') {
                return rtrim($configuredPath, DIRECTORY_SEPARATOR);
            }
        } catch (\Throwable) {
        }

        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'themes';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readThemeManifest(string $themeDir): ?array
    {
        $phpManifest = $themeDir . DIRECTORY_SEPARATOR . 'manifest.php';
        if (is_file($phpManifest)) {
            $manifest = require $phpManifest;

            return is_array($manifest) ? $manifest : null;
        }

        $jsonManifest = $themeDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!is_file($jsonManifest)) {
            return null;
        }

        try {
            $manifest = json_decode((string) file_get_contents($jsonManifest), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($manifest) ? $manifest : null;
    }

    /**
     * @param array<string, mixed> $submitted
     * @param array<string, array<string, mixed>> $existing
     * @return array{values:array<string, array<string, mixed>>, errors:array<string, list<string>>}|null
     */
    public function normalizeSubmission(array $submitted, array $existing): ?array
    {
        $values = [];
        $errors = [];

        foreach ($this->categories() as $category => $meta) {
            if (!array_key_exists($category, $submitted)) {
                foreach ($meta['fields'] as $key => $field) {
                    $values[$category][$key] = $existing[$category][$key] ?? $field['default'];
                }

                continue;
            }

            $submittedCategory = $submitted[$category];

            if (!is_array($submittedCategory)) {
                return null;
            }

            foreach ($meta['fields'] as $key => $field) {
                $fieldKey = $category . '.' . $key;
                $input = $submittedCategory[$key] ?? null;

                if (($field['input'] ?? null) === 'checkbox') {
                    $input = $input === '1';
                }

                if (($field['sensitive'] ?? false) === true && (!is_string($input) || trim($input) === '')) {
                    $values[$category][$key] = $existing[$category][$key] ?? $field['default'];
                    continue;
                }

                try {
                    $values[$category][$key] = $this->normalizeValue($field['type'], $input, $field);
                } catch (\InvalidArgumentException $exception) {
                    $errors[$fieldKey][] = $exception->getMessage();
                }
            }
        }

        return [
            'values' => $values,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $values
     * @return list<array{category:string,key:string,value:string}>
     */
    public function flattenForStorage(array $values): array
    {
        $rows = [];

        foreach ($this->categories() as $category => $meta) {
            foreach ($meta['fields'] as $key => $field) {
                $rows[] = [
                    'category' => $category,
                    'key' => $key,
                    'value' => $this->serializeValue($field['type'], $values[$category][$key] ?? $field['default']),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, string> $stored
     * @return array<string, array<string, mixed>>
     */
    public function hydrate(array $stored): array
    {
        $values = $this->defaults();

        foreach ($this->categories() as $category => $meta) {
            foreach ($meta['fields'] as $key => $field) {
                $index = $category . '.' . $key;

                if (!array_key_exists($index, $stored)) {
                    continue;
                }

                $values[$category][$key] = $this->deserializeValue($field['type'], $stored[$index]);
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $field
     */
    private function normalizeValue(string $type, mixed $input, array $field): mixed
    {
        return match ($type) {
            'bool' => (bool) $input,
            'int' => $this->normalizeInt(
                $input,
                (int) ($field['min'] ?? PHP_INT_MIN),
                isset($field['max']) ? (int) $field['max'] : null,
            ),
            'float' => $this->normalizeFloat($input, (float) ($field['min'] ?? 0)),
            'email' => $this->normalizeEmail($input),
            'timezone' => $this->normalizeTimezone($input),
            'url' => $this->normalizeUrl($input),
            'action' => false,
            default => $this->normalizeString($input, $field),
        };
    }

    /**
     * @param array<string, mixed> $field
     */
    private function normalizeString(mixed $input, array $field): string
    {
        if (!is_scalar($input) && $input !== null) {
            throw new \InvalidArgumentException('This field must be a string.');
        }

        $value = trim((string) $input);

        if (isset($field['options']) && is_array($field['options']) && !array_key_exists($value, $field['options'])) {
            throw new \InvalidArgumentException('Select a valid option.');
        }

        return $value;
    }

    private function normalizeInt(mixed $input, int $min, ?int $max = null): int
    {
        if (!is_scalar($input) || !is_numeric((string) $input)) {
            throw new \InvalidArgumentException('This field must be a number.');
        }

        $value = (int) $input;

        if ($value < $min) {
            throw new \InvalidArgumentException('This value is below the allowed minimum.');
        }
        if ($max !== null && $value > $max) {
            throw new \InvalidArgumentException('This value is above the allowed maximum.');
        }

        return $value;
    }

    private function normalizeFloat(mixed $input, float $min): float
    {
        if (!is_scalar($input) || !is_numeric((string) $input)) {
            throw new \InvalidArgumentException('This field must be numeric.');
        }

        $value = (float) $input;

        if ($value < $min) {
            throw new \InvalidArgumentException('This value is below the allowed minimum.');
        }

        return $value;
    }

    private function normalizeEmail(mixed $input): string
    {
        $value = trim((string) $input);

        if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return $value;
        }

        throw new \InvalidArgumentException('Enter a valid email address.');
    }

    private function normalizeTimezone(mixed $input): string
    {
        $value = trim((string) $input);

        if ($value !== '' && in_array($value, timezone_identifiers_list(), true)) {
            return $value;
        }

        throw new \InvalidArgumentException('Enter a valid PHP timezone identifier.');
    }

    private function normalizeUrl(mixed $input): string
    {
        $value = trim((string) $input);

        if ($value === '' || str_starts_with($value, '/') || str_starts_with($value, '#')) {
            return $value;
        }

        $url = filter_var($value, FILTER_VALIDATE_URL);
        $scheme = is_string($url) ? strtolower((string) parse_url($url, PHP_URL_SCHEME)) : '';
        if (is_string($url) && in_array($scheme, ['http', 'https'], true)) {
            return $url;
        }

        throw new \InvalidArgumentException('Enter an HTTP(S) URL or a public path beginning with /.');
    }

    private function serializeValue(string $type, mixed $value): string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    private function deserializeValue(string $type, string $value): mixed
    {
        return match ($type) {
            'bool' => $value === '1',
            'int' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }
}
