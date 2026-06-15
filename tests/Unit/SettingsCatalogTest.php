<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Settings\Support\SettingsCatalog;
use PHPUnit\Framework\TestCase;

final class SettingsCatalogTest extends TestCase
{
    public function testLogoFieldUsesFileUploadControl(): void
    {
        $catalog = new SettingsCatalog();
        $logoField = $catalog->categories()['ui']['fields']['logo_url'];

        self::assertSame('file', $logoField['input']);
        self::assertSame('string', $logoField['type']);
        self::assertSame('image/*,.svg', $logoField['accept']);
    }

    public function testTimezoneFieldUsesSelectOptions(): void
    {
        $catalog = new SettingsCatalog();
        $timezoneField = $catalog->categories()['app']['fields']['timezone'];

        self::assertSame('select', $timezoneField['input']);
        self::assertArrayHasKey('Asia/Dhaka', $timezoneField['options']);
        self::assertArrayHasKey('UTC', $timezoneField['options']);
    }

    public function testInterfaceThemesAreSplitByThemeType(): void
    {
        $catalog = new SettingsCatalog();
        $interfaceFields = $catalog->categories()['ui']['fields'];

        self::assertSame('select', $interfaceFields['theme']['input']);
        self::assertSame('select', $interfaceFields['admin_theme']['input']);
        self::assertSame(['default' => 'default'], $interfaceFields['theme']['options']);
        self::assertArrayHasKey('admin', $interfaceFields['admin_theme']['options']);
        self::assertArrayHasKey('executive', $interfaceFields['admin_theme']['options']);
        self::assertSame('Admin Default', $interfaceFields['admin_theme']['options']['admin']);
        self::assertSame('Executive', $interfaceFields['admin_theme']['options']['executive']);
    }

    public function testNormalizeSubmissionCoercesTypesAndRetainsSensitiveValues(): void
    {
        $catalog = new SettingsCatalog();
        $existing = $catalog->defaults();
        $existing['email']['smtp_pass'] = 'keep-secret';

        $result = $catalog->normalizeSubmission([
            'app' => [
                'name' => ' Ops Console ',
                'env' => 'production',
                'debug' => '1',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'maintenance_mode' => null,
            ],
            'system' => [
                'pagination_limit' => '25',
                'max_upload_size' => '20M',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i:s',
            ],
            'security' => [
                'password_policy' => '12 chars',
                'login_attempt_limit' => '7',
                '2fa_enabled' => '1',
            ],
            'email' => [
                'smtp_host' => 'smtp.example.test',
                'smtp_port' => '2525',
                'smtp_user' => 'mailer',
                'smtp_pass' => '',
                'from_email' => 'alerts@example.test',
            ],
            'ui' => [
                'theme' => 'default',
                'admin_theme' => 'executive',
                'logo_url' => 'https://example.test/logo.svg',
            ],
            'cache' => [
                'driver' => 'memory',
                'ttl' => '1800',
            ],
            'logging' => [
                'level' => 'error',
                'retention_days' => '14',
            ],
            'payment' => [
                'currency' => 'BDT',
                'tax_rate' => '15.5',
            ],
        ], $existing);

        self::assertNotNull($result);
        self::assertSame([], $result['errors']);
        self::assertSame('Ops Console', $result['values']['app']['name']);
        self::assertTrue($result['values']['app']['debug']);
        self::assertSame(25, $result['values']['system']['pagination_limit']);
        self::assertSame('keep-secret', $result['values']['email']['smtp_pass']);
        self::assertSame(15.5, $result['values']['payment']['tax_rate']);
    }

    public function testUncheckingCheckboxReturnsFalse(): void
    {
        $catalog = new SettingsCatalog();
        $existing = $catalog->defaults();
        $existing['app']['debug'] = true;
        $existing['app']['maintenance_mode'] = true;
        $existing['cache']['enabled'] = true;

        $result = $catalog->normalizeSubmission([
            'app' => [
                'name' => $existing['app']['name'],
                'env' => $existing['app']['env'],
                'debug' => null,
                'timezone' => $existing['app']['timezone'],
                'locale' => $existing['app']['locale'],
                'maintenance_mode' => null,
            ],
            'system' => $existing['system'],
            'security' => $existing['security'],
            'email' => $existing['email'],
            'ui' => $existing['ui'],
            'cache' => [
                'driver' => $existing['cache']['driver'],
                'ttl' => $existing['cache']['ttl'],
                'enabled' => null,
            ],
            'logging' => $existing['logging'],
            'payment' => $existing['payment'],
        ], $existing);

        self::assertNotNull($result);
        self::assertSame([], $result['errors']);
        self::assertFalse($result['values']['app']['debug']);
        self::assertFalse($result['values']['app']['maintenance_mode']);
        self::assertFalse($result['values']['cache']['enabled']);
    }

    public function testCheckingCheckboxReturnsTrue(): void
    {
        $catalog = new SettingsCatalog();
        $existing = $catalog->defaults();
        $existing['app']['debug'] = false;

        $result = $catalog->normalizeSubmission([
            'app' => [
                'name' => $existing['app']['name'],
                'env' => $existing['app']['env'],
                'debug' => '1',
                'timezone' => $existing['app']['timezone'],
                'locale' => $existing['app']['locale'],
                'maintenance_mode' => $existing['app']['maintenance_mode'],
            ],
            'system' => $existing['system'],
            'security' => $existing['security'],
            'email' => $existing['email'],
            'ui' => $existing['ui'],
            'cache' => $existing['cache'],
            'logging' => $existing['logging'],
            'payment' => $existing['payment'],
        ], $existing);

        self::assertNotNull($result);
        self::assertSame([], $result['errors']);
        self::assertTrue($result['values']['app']['debug']);
    }
}
