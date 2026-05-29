<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AdminListState;
use PHPUnit\Framework\TestCase;

final class AdminListStateTest extends TestCase
{
    public function testStateNormalizesSearchFilterSortDirectionAndPage(): void
    {
        $state = (new AdminListState())->stateFrom([
            'q' => '  alice  ',
            'filter' => ' active ',
            'sort' => ' name ',
            'direction' => 'ASC',
            'page' => '3',
        ]);

        self::assertSame('alice', $state['query']);
        self::assertSame('active', $state['filter']);
        self::assertSame('name', $state['sort']);
        self::assertSame('asc', $state['direction']);
        self::assertSame(3, $state['page']);
    }
}
