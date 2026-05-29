<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AdminToast;
use PHPUnit\Framework\TestCase;

final class AdminToastTest extends TestCase
{
    public function testItNormalizesSessionFlashDataIntoToastItems(): void
    {
        $items = AdminToast::fromSessionData([
            'users.notice' => 'User created successfully.',
            'settings.errors' => [
                '_global' => ['Choose a valid backup frequency.'],
            ],
        ]);

        self::assertCount(2, $items);
        self::assertSame('success', $items[0]['tone']);
        self::assertSame('Success', $items[0]['title']);
        self::assertSame('User created successfully.', $items[0]['message']);
        self::assertSame('badge-check', $items[0]['icon']);
        self::assertSame('error', $items[1]['tone']);
        self::assertSame('Validation error', $items[1]['title']);
        self::assertSame('Choose a valid backup frequency.', $items[1]['message']);
        self::assertSame('circle-x', $items[1]['icon']);
    }
}
