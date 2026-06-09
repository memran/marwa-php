<?php

declare(strict_types=1);

namespace App\Support\Pagination;

use InvalidArgumentException;

final class Paginator
{
    /**
     * @param array<string, mixed> $query
     */
    public function __construct(
        private readonly string $path = '/',
        private readonly array $query = [],
        private readonly string $pageName = 'page',
        private readonly int $window = 2,
        private readonly int $maxPerPage = 100,
    ) {
    }

    /**
     * @param array<string, mixed> $source
     * @return array{
     *     data: array<int, mixed>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    public function normalize(array $source): array
    {
        if (!array_key_exists('data', $source) || !is_array($source['data'])) {
            throw new InvalidArgumentException('Pagination source must contain a data array.');
        }

        $total = max(0, (int) ($source['total'] ?? 0));
        $perPage = $this->normalizePerPage($source['per_page'] ?? 1);
        $calculatedLastPage = $total === 0 ? 1 : max(1, (int) ceil($total / $perPage));
        $lastPage = max(1, (int) ($source['last_page'] ?? 1), $calculatedLastPage);

        if ($total === 0) {
            $currentPage = 1;
            $lastPage = 1;
        } else {
            $currentPage = max(1, (int) ($source['current_page'] ?? 1));
            $currentPage = min($currentPage, $lastPage);
        }

        $from = $total === 0 ? null : (($currentPage - 1) * $perPage) + 1;
        $to = $total === 0 ? null : min($total, $currentPage * $perPage);

        return [
            'data' => array_values($source['data']),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function url(int $page): string
    {
        $page = max(1, $page);
        $query = $this->normalizedQuery();
        $query[$this->pageName] = $page;

        $queryString = http_build_query($query);

        return $this->normalizedPath() . ($queryString !== '' ? '?' . $queryString : '');
    }

    public function path(): string
    {
        return $this->normalizedPath();
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->normalizedQuery();
    }

    /**
     * @return list<PageLink>
     */
    public function pageLinks(int $currentPage, int $lastPage): array
    {
        $currentPage = max(1, $currentPage);
        $lastPage = max(1, $lastPage);

        if ($lastPage === 1) {
            return [
                new PageLink(1, '1', $this->url(1), true, false),
            ];
        }

        $pages = [1, $currentPage, $lastPage];

        for ($offset = 1; $offset <= max(0, $this->window); $offset++) {
            $pages[] = $currentPage - $offset;
            $pages[] = $currentPage + $offset;
        }

        $pages = array_values(array_unique(array_filter(
            $pages,
            static fn (int $page): bool => $page >= 1 && $page <= $lastPage
        )));
        sort($pages);

        $links = [];
        $previousPage = null;

        foreach ($pages as $page) {
            if ($previousPage !== null && $page > $previousPage + 1) {
                $links[] = new PageLink(null, '...', null, false, true);
            }

            $links[] = new PageLink($page, (string) $page, $this->url($page), $page === $currentPage, false);
            $previousPage = $page;
        }

        return $links;
    }

    /**
     * @return array<string, mixed>
     */
    public function links(int $currentPage, int $lastPage): array
    {
        return [
            'first' => $this->url(1),
            'previous' => $currentPage > 1 ? $this->url($currentPage - 1) : null,
            'next' => $currentPage < $lastPage ? $this->url($currentPage + 1) : null,
            'last' => $this->url($lastPage),
            'pages' => $this->pageLinks($currentPage, $lastPage),
        ];
    }

    private function normalizedPath(): string
    {
        if (trim($this->path) !== '') {
            return $this->path;
        }

        if (function_exists('app')) {
            try {
                if (app()->has(\Psr\Http\Message\ServerRequestInterface::class)) {
                    $request = app()->make(\Psr\Http\Message\ServerRequestInterface::class);
                    $path = $request->getUri()->getPath();

                    if ($path !== '') {
                        return $path;
                    }
                }
            } catch (\Throwable) {
                // Fall back to the root path when no request is available.
            }
        }

        return '/';
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedQuery(): array
    {
        $query = $this->query;
        unset($query[$this->pageName]);

        $clean = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function normalizePerPage(mixed $value): int
    {
        $perPage = max(1, (int) $value);

        return min($perPage, max(1, $this->maxPerPage));
    }
}
