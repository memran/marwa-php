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

    public function testRequestParamsPreservesTableStateAndColumns(): void
    {
        $state = [
            'query' => 'alice',
            'filter' => 'active',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 3,
        ];

        $params = (new AdminListState())->requestParams($state, ['name', 'email']);

        self::assertSame('alice', $params['q']);
        self::assertSame('active', $params['filter']);
        self::assertSame('name', $params['sort']);
        self::assertSame('asc', $params['direction']);
        self::assertSame(3, $params['page']);
        self::assertSame(['name', 'email'], $params['columns']);
    }

    public function testTableParamsBuildsRequestAndPaginationPayloadsTogether(): void
    {
        $state = [
            'query' => 'alice',
            'filter' => 'active',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 3,
        ];

        $params = (new AdminListState())->tableParams($state, ['name'], ['name', 'email']);

        self::assertSame('alice', $params['request']['q']);
        self::assertSame(['name'], $params['request']['columns']);
        self::assertSame('alice', $params['pagination']['q']);
        self::assertSame(['name', 'email'], $params['pagination']['columns']);
    }
}
