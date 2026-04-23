<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Settings\Support\SettingsCatalog;
use PHPUnit\Framework\TestCase;

final class SettingsCatalogTest extends TestCase
{
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
                'logo_url' => 'https://example.test/logo.svg',
                'layout_mode' => 'compact',
                'sidebar_state' => 'collapsed',
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
}
