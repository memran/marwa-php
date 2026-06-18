<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Support\PermissionRepository;
use App\Support\AdminListState;
use App\Support\Pagination\PaginationResult;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionIndexPage
{
    public function __construct(
        private readonly AdminListState $listState,
        private readonly PermissionRepository $permissions,
        private readonly RoleModuleNotice $notice,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function viewData(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $state = $this->listState->stateFrom(
            $params,
            'q',
            'group',
            'sort',
            'direction',
            'page'
        );

        $group = isset($params['group']) ? trim((string) $params['group']) : '';
        $sort = isset($params['sort']) ? trim((string) $params['sort']) : 'group';
        $direction = isset($params['direction']) ? strtolower(trim((string) $params['direction'])) : 'asc';

        if ($group === 'all') {
            $group = '';
        }

        if ($sort === '' || $sort === 'created_at') {
            $sort = 'group';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $pageData = $this->permissions->paginatedGroupedFiltered(
            $state['query'],
            $group,
            $state['page'],
            null,
            $sort,
            $direction
        );

        return [
            'permissions' => $pageData['groups'],
            'group_options' => $this->permissions->groupNames(),
            'query' => $state['query'],
            'group' => $group,
            'sort' => $sort,
            'direction' => $direction,
            'visible_permissions' => (int) $pageData['total'],
            'group_count' => count($pageData['groups']),
            'total_permissions' => $this->permissions->count(),
            'create_url' => '/admin/permissions/create',
            'pagination' => PaginationResult::fromArray([
                'data' => $pageData['data'] ?? [],
                'total' => (int) ($pageData['total'] ?? 0),
                'per_page' => (int) ($pageData['per_page'] ?? 1),
                'current_page' => (int) ($pageData['current_page'] ?? 1),
                'last_page' => (int) ($pageData['last_page'] ?? 1),
            ], '/admin/permissions', [
                'q' => $state['query'],
                'group' => $group,
                'sort' => $sort,
                'direction' => $direction,
            ]),
            'notice' => $this->notice->pull('permissions.notice'),
        ];
    }
}
