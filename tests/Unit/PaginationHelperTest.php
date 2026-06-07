<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PaginationHelperTest extends TestCase
{
    public function testPaginationViewDataHelperBuildsStandardPaginationPayload(): void
    {
        $viewData = pagination_view_data([
            'total' => 42,
            'per_page' => 10,
            'current_page' => 2,
            'last_page' => 5,
        ], '/admin/users', [
            'q' => 'john',
            'filter' => 'active',
        ]);

        self::assertSame('Showing 11-20 of 42 results', $viewData['summary']);
        self::assertSame([1, 2, 3, 4, 5], array_map(
            static fn (array $link): int => $link['page'],
            $viewData['links']
        ));
        self::assertSame('/admin/users?q=john&filter=active&page=2', $viewData['links'][1]['url']);
        self::assertTrue($viewData['links'][1]['active']);
    }
}
