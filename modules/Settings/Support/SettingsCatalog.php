<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

final class SettingsCatalog
{
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
            'app' => [
                'label' => 'Application',
                'description' => 'Starter identity and global runtime defaults.',
                'fields' => [
                    'name' => ['label' => 'App name', 'input' => 'text', 'type' => 'string', 'default' => (string) env('APP_NAME', 'MarwaPHP'), 'help' => 'Applied to the runtime app config and shared labels immediately after save.'],
                    'env' => ['label' => 'Environment', 'input' => 'select', 'type' => 'string', 'default' => (string) env('APP_ENV', 'production'), 'options' => ['production' => 'production', 'staging' => 'staging', 'development' => 'development', 'testing' => 'testing', 'local' => 'local']],
                    'debug' => ['label' => 'Debug mode', 'input' => 'checkbox', 'type' => 'bool', 'default' => (bool) env('APP_DEBUG', false), 'help' => 'Mirrored into the runtime view and error config.'],
                    'timezone' => ['label' => 'Timezone', 'input' => 'text', 'type' => 'timezone', 'default' => (string) env('TIMEZONE', 'UTC')],
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
                    '2fa_enabled' => ['label' => '2FA enabled', 'input' => 'checkbox', 'type' => 'bool', 'default' => false],
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
                    'theme' => ['label' => 'Frontend theme', 'input' => 'text', 'type' => 'string', 'default' => (string) env('FRONTEND_THEME', 'default')],
                    'admin_theme' => ['label' => 'Admin theme', 'input' => 'text', 'type' => 'string', 'default' => (string) env('ADMIN_THEME', 'admin')],
                    'logo_url' => ['label' => 'Logo URL', 'input' => 'url', 'type' => 'url', 'default' => ''],
                    'layout_mode' => ['label' => 'Layout mode', 'input' => 'select', 'type' => 'string', 'default' => 'compact', 'options' => ['compact' => 'compact', 'comfortable' => 'comfortable']],
                    'sidebar_state' => ['label' => 'Sidebar state', 'input' => 'select', 'type' => 'string', 'default' => 'expanded', 'options' => ['expanded' => 'expanded', 'collapsed' => 'collapsed']],
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
                    'tax_rate' => ['label' => 'Tax rate', 'input' => 'number', 'type' => 'float', 'default' => 0.0, 'min' => 0, 'step' => '0.01'],
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
     * @param array<string, mixed> $submitted
     * @param array<string, array<string, mixed>> $existing
     * @return array{values:array<string, array<string, mixed>>, errors:array<string, list<string>>}|null
     */
    public function normalizeSubmission(array $submitted, array $existing): ?array
    {
        $values = [];
        $errors = [];

        foreach ($this->categories() as $category => $meta) {
            $submittedCategory = $submitted[$category] ?? [];

            if (!is_array($submittedCategory)) {
                return null;
            }

            foreach ($meta['fields'] as $key => $field) {
                $fieldKey = $category . '.' . $key;
                $input = $submittedCategory[$key] ?? null;

                if (($field['input'] ?? null) === 'checkbox') {
                    $input = $input !== null;
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
            'int' => $this->normalizeInt($input, (int) ($field['min'] ?? PHP_INT_MIN)),
            'float' => $this->normalizeFloat($input, (float) ($field['min'] ?? 0)),
            'email' => $this->normalizeEmail($input),
            'url' => $this->normalizeUrl($input),
            'timezone' => $this->normalizeTimezone($input),
            'list' => $this->normalizeList($input),
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

    private function normalizeInt(mixed $input, int $min): int
    {
        if (!is_scalar($input) || !is_numeric((string) $input)) {
            throw new \InvalidArgumentException('This field must be a number.');
        }

        $value = (int) $input;

        if ($value < $min) {
            throw new \InvalidArgumentException('This value is below the allowed minimum.');
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

    private function normalizeUrl(mixed $input): string
    {
        $value = trim((string) $input);

        if ($value === '' || filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return $value;
        }

        throw new \InvalidArgumentException('Enter a valid URL.');
    }

    private function normalizeTimezone(mixed $input): string
    {
        $value = trim((string) $input);

        if ($value !== '' && in_array($value, timezone_identifiers_list(), true)) {
            return $value;
        }

        throw new \InvalidArgumentException('Enter a valid PHP timezone identifier.');
    }

    /**
     * @return list<string>
     */
    private function normalizeList(mixed $input): array
    {
        if (!is_scalar($input) && $input !== null) {
            throw new \InvalidArgumentException('This field must be text.');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim((string) $input)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines
        ), static fn (string $line): bool => $line !== ''));
    }

    private function serializeValue(string $type, mixed $value): string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'list' => json_encode(is_array($value) ? array_values($value) : [], JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }

    private function deserializeValue(string $type, string $value): mixed
    {
        return match ($type) {
            'bool' => $value === '1',
            'int' => (int) $value,
            'float' => (float) $value,
            'list' => $this->decodeList($value),
            default => $value,
        };
    }

    /**
     * @return list<string>
     */
    private function decodeList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $decoded
        ), static fn (string $item): bool => $item !== ''));
    }
}
