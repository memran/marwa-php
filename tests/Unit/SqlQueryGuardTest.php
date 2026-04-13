<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\DatabaseManager\Support\SqlQueryGuard;
use PHPUnit\Framework\TestCase;

final class SqlQueryGuardTest extends TestCase
{
    public function testSanitizeRejectsCommentsAndMultipleStatements(): void
    {
        $guard = new SqlQueryGuard();

        $this->expectException(\InvalidArgumentException::class);
        $guard->sanitize("SELECT * FROM users; DELETE FROM users");
    }

    public function testSanitizeAllowsSingleStatementAndTrimsTrailingSemicolon(): void
    {
        $guard = new SqlQueryGuard();
        $result = $guard->sanitize(" SELECT * FROM users; \n");

        self::assertSame('SELECT * FROM users', $result['normalized']);
    }

    public function testRequiresConfirmationForDestructiveQueries(): void
    {
        $guard = new SqlQueryGuard();

        self::assertTrue($guard->requiresConfirmation('DELETE FROM users'));
        self::assertTrue($guard->requiresConfirmation('UPDATE users SET role = "admin"'));
        self::assertFalse($guard->requiresConfirmation('SELECT * FROM users'));
        self::assertFalse($guard->requiresConfirmation('INSERT INTO users (name) VALUES ("A")'));
    }
}
