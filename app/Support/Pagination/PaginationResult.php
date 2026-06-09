<?php

declare(strict_types=1);

namespace App\Support\Pagination;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final class PaginationResult implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @param array<int, mixed> $items
     * @param array<string, scalar|list<string>|null> $query
     */
    private function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $currentPage,
        private readonly int $lastPage,
        private readonly ?int $from,
        private readonly ?int $to,
        private readonly string $path,
        private readonly array $query,
        private readonly string $pageName,
        private readonly int $window,
        private readonly int $maxPerPage,
    ) {
    }

    /**
     * @param array{data:array<int, mixed>, total:int, per_page:int, current_page:int, last_page:int} $source
     * @param array<string, scalar|list<string>|null> $query
     */
    public static function fromArray(
        array $source,
        string $path = '/',
        array $query = [],
        string $pageName = 'page',
        int $window = 2,
        int $maxPerPage = 100
    ): self {
        $paginator = new Paginator($path, $query, $pageName, $window, $maxPerPage);
        $normalized = $paginator->normalize($source);

        return new self(
            $normalized['data'],
            $normalized['total'],
            $normalized['per_page'],
            $normalized['current_page'],
            $normalized['last_page'],
            $normalized['from'],
            $normalized['to'],
            $paginator->path(),
            $paginator->query(),
            $pageName,
            max(0, $window),
            max(1, $maxPerPage)
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return array<int, mixed>
     */
    public function data(): array
    {
        return $this->items;
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        if ($this->items === []) {
            return null;
        }

        $lastKey = array_key_last($this->items);

        return $this->items[$lastKey];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function from(): ?int
    {
        return $this->from;
    }

    public function to(): ?int
    {
        return $this->to;
    }

    public function hasPages(): bool
    {
        return $this->total > 0 && $this->lastPage > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->total > 0 && $this->currentPage < $this->lastPage;
    }

    public function hasPreviousPage(): bool
    {
        return $this->total > 0 && $this->currentPage > 1;
    }

    public function nextUrl(): ?string
    {
        return $this->hasNextPage() ? $this->url($this->currentPage + 1) : null;
    }

    public function previousUrl(): ?string
    {
        return $this->hasPreviousPage() ? $this->url($this->currentPage - 1) : null;
    }

    public function firstPageUrl(): string
    {
        return $this->url(1);
    }

    public function lastPageUrl(): string
    {
        return $this->url($this->lastPage);
    }

    public function url(int $page): string
    {
        return (new Paginator($this->path, $this->query, $this->pageName, $this->window, $this->maxPerPage))
            ->url($page);
    }

    /**
     * @return list<PageLink>
     */
    public function pages(): array
    {
        return (new Paginator($this->path, $this->query, $this->pageName, $this->window, $this->maxPerPage))
            ->pageLinks($this->currentPage, $this->lastPage);
    }

    /**
     * @return array{first:string,previous:?string,next:?string,last:string,pages:list<PageLink>}
     */
    public function links(): array
    {
        return (new Paginator($this->path, $this->query, $this->pageName, $this->window, $this->maxPerPage))
            ->links($this->currentPage, $this->lastPage);
    }

    public function summary(): string
    {
        return $this->total === 0
            ? 'Showing 0 results'
            : sprintf('Showing %d-%d of %d results', $this->from ?? 0, $this->to ?? 0, $this->total);
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, scalar|list<string>|null>
     */
    public function query(): array
    {
        return $this->query;
    }

    public function pageName(): string
    {
        return $this->pageName;
    }

    /**
     * @return array{
     *     data: array<int, mixed>,
     *     meta: array{total:int,per_page:int,current_page:int,last_page:int,from:int|null,to:int|null},
     *     links: array{first:string,previous:?string,next:?string,last:string,pages:list<array{number:int|null,label:string,url:string|null,active:bool,disabled:bool}>}
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => $this->normalizeForOutput($this->items),
            'meta' => [
                'total' => $this->total,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
                'from' => $this->from,
                'to' => $this->to,
            ],
            'links' => [
                'first' => $this->firstPageUrl(),
                'previous' => $this->previousUrl(),
                'next' => $this->nextUrl(),
                'last' => $this->lastPageUrl(),
                'pages' => array_map(
                    static fn (PageLink $link): array => $link->toArray(),
                    $this->pages()
                ),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, mixed>
     */
    private function normalizeForOutput(array $items): array
    {
        return array_map(static function (mixed $item): mixed {
            if (is_array($item)) {
                return $item;
            }

            if ($item instanceof JsonSerializable) {
                return $item->jsonSerialize();
            }

            if (is_object($item) && method_exists($item, 'toArray')) {
                return $item->toArray();
            }

            if (is_object($item)) {
                return get_object_vars($item);
            }

            return $item;
        }, $items);
    }
}
