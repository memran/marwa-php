<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AdminPagination;
use PHPUnit\Framework\TestCase;

final class AdminPaginationTest extends TestCase
{
    public function testViewDataLimitsThePageWindowForLargeResultSets(): void
    {
        $pagination = new AdminPagination();

        $viewData = $pagination->viewData([
            'total' => 100000,
            'per_page' => 10,
            'current_page' => 5000,
            'last_page' => 10000,
        ], '/admin/users', [
            'q' => 'admin',
        ]);

        self::assertSame('Showing 49991-50000 of 100000 results', $viewData['summary']);
        self::assertCount(7, $viewData['links']);
        self::assertSame([1, 4998, 4999, 5000, 5001, 5002, 10000], array_map(
            static fn (array $link): int => $link['page'],
            $viewData['links']
        ));
        self::assertTrue($viewData['links'][3]['active']);
        self::assertSame('/admin/users?q=admin&page=5000', $viewData['links'][3]['url']);
    }
}
