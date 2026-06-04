<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AdminThemeResolver;
use PHPUnit\Framework\TestCase;

final class AdminThemeResolverTest extends TestCase
{
    public function testResolveFallsBackToAdminThemeWhenConfiguredThemeIsNotAdmin(): void
    {
        $resolver = new AdminThemeResolver();

        self::assertSame('admin', $resolver->resolve('default'));
    }

    public function testResolveKeepsAdminThemeWhenConfiguredThemeIsAdminType(): void
    {
        $resolver = new AdminThemeResolver();

        self::assertSame('executive', $resolver->resolve('executive'));
    }
}
