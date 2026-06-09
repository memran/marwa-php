<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Pagination\PaginationResult;
use App\Support\Pagination\PageLink;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

final class PaginationResultTest extends TestCase
{
    public function testWrapsRawPaginationArrayAndExposesItems(): void
    {
        $first = new PaginationTestRecord('Alice', 'alice@example.test');
        $second = new PaginationTestRecord('Bob', 'bob@example.test');

        $pagination = PaginationResult::fromArray([
            'data' => [$first, $second],
            'total' => 2,
            'per_page' => 10,
            'current_page' => 1,
            'last_page' => 1,
        ], path: '/admin/users', query: [
            'search' => 'admin',
            'sort' => 'name',
            'page' => 99,
        ]);

        self::assertSame('/admin/users', $pagination->path());
        self::assertSame(['search' => 'admin', 'sort' => 'name'], $pagination->query());
        self::assertSame($first, $pagination->first());
        self::assertSame($second, $pagination->last());
        self::assertSame([$first, $second], $pagination->items());
        self::assertSame([$first, $second], $pagination->data());
    }

    public function testSupportsIterationAndCountable(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [
                new PaginationTestRecord('Alice', 'alice@example.test'),
                new PaginationTestRecord('Bob', 'bob@example.test'),
            ],
            'total' => 2,
            'per_page' => 10,
            'current_page' => 1,
            'last_page' => 1,
        ]);

        self::assertInstanceOf(IteratorAggregate::class, $pagination);
        self::assertInstanceOf(Countable::class, $pagination);
        self::assertSame(2, count($pagination));

        $names = [];
        foreach ($pagination as $item) {
            $names[] = $item->name;
        }

        self::assertSame(['Alice', 'Bob'], $names);
    }

    public function testFromAndToAreCalculatedForNormalResults(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [
                new PaginationTestRecord('Item 1', 'one@example.test'),
                new PaginationTestRecord('Item 2', 'two@example.test'),
            ],
            'total' => 25,
            'per_page' => 10,
            'current_page' => 2,
            'last_page' => 3,
        ]);

        self::assertSame(11, $pagination->from());
        self::assertSame(20, $pagination->to());
        self::assertTrue($pagination->hasPages());
        self::assertTrue($pagination->hasPreviousPage());
        self::assertTrue($pagination->hasNextPage());
    }

    public function testGeneratesUrlsAndPreservesQueryString(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [
                new PaginationTestRecord('Item 1', 'one@example.test'),
            ],
            'total' => 25,
            'per_page' => 10,
            'current_page' => 2,
            'last_page' => 3,
        ], path: '/admin/users', query: [
            'search' => 'admin',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 99,
        ]);

        self::assertSame('/admin/users?search=admin&sort=name&direction=asc&page=1', $pagination->firstPageUrl());
        self::assertSame('/admin/users?search=admin&sort=name&direction=asc&page=3', $pagination->lastPageUrl());
        self::assertSame('/admin/users?search=admin&sort=name&direction=asc&page=1', $pagination->previousUrl());
        self::assertSame('/admin/users?search=admin&sort=name&direction=asc&page=3', $pagination->nextUrl());
        self::assertSame('/admin/users?search=admin&sort=name&direction=asc&page=2', $pagination->url(2));
    }

    public function testSupportsCustomPageName(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [
                new PaginationTestRecord('Item 1', 'one@example.test'),
            ],
            'total' => 25,
            'per_page' => 10,
            'current_page' => 2,
            'last_page' => 3,
        ], path: '/admin/users', query: [
            'search' => 'admin',
            'users_page' => 9,
        ], pageName: 'users_page');

        self::assertSame('/admin/users?search=admin&users_page=1', $pagination->firstPageUrl());
        self::assertSame('/admin/users?search=admin&users_page=2', $pagination->url(2));
        self::assertSame('/admin/users?search=admin&users_page=3', $pagination->lastPageUrl());
        self::assertSame(['search' => 'admin'], $pagination->query());
        self::assertSame('users_page', $pagination->pageName());
    }

    public function testBuildsPagesWithEllipsis(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [
                new PaginationTestRecord('Item 1', 'one@example.test'),
            ],
            'total' => 100,
            'per_page' => 10,
            'current_page' => 5,
            'last_page' => 10,
        ], path: '/admin/users');

        $pages = $pagination->pages();

        self::assertSame(['1', '...', '3', '4', '5', '6', '7', '...', '10'], array_map(
            static fn (PageLink $page): string => $page->label,
            $pages
        ));
        self::assertSame([false, true, false, false, false, false, false, true, false], array_map(
            static fn (PageLink $page): bool => $page->disabled,
            $pages
        ));
        self::assertTrue($pages[4]->active);
    }

    public function testHandlesEmptyResultSet(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [],
            'total' => 0,
            'per_page' => 10,
            'current_page' => 1,
            'last_page' => 1,
        ], path: '/admin/users');

        self::assertTrue($pagination->isEmpty());
        self::assertFalse($pagination->hasPages());
        self::assertNull($pagination->from());
        self::assertNull($pagination->to());
        self::assertNull($pagination->previousUrl());
        self::assertNull($pagination->nextUrl());
        self::assertSame('/admin/users?page=1', $pagination->firstPageUrl());
        self::assertSame('/admin/users?page=1', $pagination->lastPageUrl());
        self::assertCount(1, $pagination->pages());
        self::assertTrue($pagination->pages()[0]->active);
    }

    public function testSerializesToJson(): void
    {
        $pagination = PaginationResult::fromArray([
            'data' => [
                new PaginationTestRecord('Alice', 'alice@example.test'),
            ],
            'total' => 1,
            'per_page' => 10,
            'current_page' => 1,
            'last_page' => 1,
        ], path: '/admin/users', query: [
            'search' => 'alice',
        ]);

        $json = $pagination->toJson();
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $decoded['meta']['total']);
        self::assertSame('Alice', $decoded['data'][0]['name']);
        self::assertSame('/admin/users?search=alice&page=1', $decoded['links']['first']);
        self::assertSame('/admin/users?search=alice&page=1', $decoded['links']['pages'][0]['url']);
    }
}

final class PaginationTestRecord implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }

    /**
     * @return array{name:string,email:string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    /**
     * @return array{name:string,email:string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
