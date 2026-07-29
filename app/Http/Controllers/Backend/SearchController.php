<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Support\Search\GlobalSearch;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearch $globalSearch,
    ) {}

    public function index(): ResponseInterface
    {
        $query = trim((string) request('q', ''));
        $scope = trim((string) request('scope', GlobalSearch::SCOPE_ALL));
        $page = max(1, (int) request('page', 1));

        return $this->view('@Shared/search', [
            'search' => $this->globalSearch->search($query, $scope, $page),
            'scopes' => $this->globalSearch->scopes(),
        ]);
    }
}
