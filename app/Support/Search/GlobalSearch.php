<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Modules\Activity\Models\Activity;
use App\Modules\ApiToken\Models\ApiToken;
use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use App\Support\AdminPagination;
use App\Support\AdminSearch;
use App\Support\Pagination\PaginationResult;
use App\Support\PermissionGate;
use Marwa\DB\ORM\QueryBuilder;

/**
 * Aggregates permission-gated searches across admin modules for the
 * global search page so the controller stays thin.
 */
final class GlobalSearch
{
    public const SCOPE_ALL = 'all';

    private const GROUP_LIMIT = 5;
    private const SCOPE_PER_PAGE = 15;
    private const PATH = '/admin/search';

    public function __construct(
        private readonly PermissionGate $gate,
        private readonly AdminSearch $search,
        private readonly AdminPagination $pagination,
    ) {}

    /**
     * @return list<array{value:string,label:string}>
     */
    public function scopes(): array
    {
        $scopes = [['value' => self::SCOPE_ALL, 'label' => 'All records']];

        foreach ($this->allowedProviders() as $key => $provider) {
            $scopes[] = ['value' => $key, 'label' => $provider['label']];
        }

        return $scopes;
    }

    /**
     * @return array{
     *   query:string,
     *   active_scope:string,
     *   groups:list<array<string,mixed>>,
     *   result_count:int,
     *   pagination:?PaginationResult
     * }
     */
    public function search(string $query, string $scope, int $page): array
    {
        $query = trim($query);
        $providers = $this->allowedProviders();
        $activeScope = isset($providers[$scope]) ? $scope : self::SCOPE_ALL;

        if ($query === '' || $providers === []) {
            return [
                'query' => $query,
                'active_scope' => $activeScope,
                'groups' => [],
                'result_count' => 0,
                'pagination' => null,
            ];
        }

        if ($activeScope !== self::SCOPE_ALL) {
            [$group, $pagination] = $this->scopedGroup($activeScope, $providers[$activeScope], $query, $page);

            return [
                'query' => $query,
                'active_scope' => $activeScope,
                'groups' => [$group],
                'result_count' => $group['count'],
                'pagination' => $pagination,
            ];
        }

        $groups = [];
        $resultCount = 0;

        foreach ($providers as $key => $provider) {
            try {
                $result = ($provider['handler'])($query, 1, self::GROUP_LIMIT);
            } catch (\Throwable) {
                // Skip providers whose module tables are unavailable
                // (module disabled or migrations not run yet).
                continue;
            }

            $resultCount += $result['count'];

            $groups[] = [
                'label' => $provider['label'],
                'icon' => $provider['icon'],
                'count' => $result['count'],
                'items' => $result['items'],
                'view_all_url' => $result['count'] > self::GROUP_LIMIT
                    ? self::PATH . '?' . http_build_query(['q' => $query, 'scope' => $key])
                    : '',
                'view_all_label' => 'View all ' . $result['count'],
            ];
        }

        return [
            'query' => $query,
            'active_scope' => $activeScope,
            'groups' => $groups,
            'result_count' => $resultCount,
            'pagination' => null,
        ];
    }

    /**
     * @param array{label:string,icon:string,handler:callable} $provider
     * @return array{0:array<string,mixed>,1:?PaginationResult}
     */
    private function scopedGroup(string $scope, array $provider, string $query, int $page): array
    {
        try {
            $result = ($provider['handler'])($query, $page, self::SCOPE_PER_PAGE);
        } catch (\Throwable) {
            $result = ['count' => 0, 'items' => []];
        }

        $pagination = null;
        if ($result['count'] > self::SCOPE_PER_PAGE) {
            $pagination = $this->pagination->viewData([
                'data' => $result['items'],
                'total' => $result['count'],
                'per_page' => self::SCOPE_PER_PAGE,
                'current_page' => $page,
                'last_page' => (int) ceil($result['count'] / self::SCOPE_PER_PAGE),
            ], self::PATH, ['q' => $query, 'scope' => $scope]);
        }

        return [[
            'label' => $provider['label'],
            'icon' => $provider['icon'],
            'count' => $result['count'],
            'items' => $result['items'],
            'view_all_url' => '',
        ], $pagination];
    }

    /**
     * @return array<string, array{label:string,icon:string,permission:string,handler:(callable(string,int,int):array{count:int,items:list<array<string,mixed>>})}>
     */
    private function providers(): array
    {
        return [
            'users' => [
                'label' => 'Users',
                'icon' => 'users',
                'permission' => 'users.view',
                'handler' => fn (string $query, int $page, int $perPage): array => $this->searchUsers($query, $page, $perPage),
            ],
            'roles' => [
                'label' => 'Roles',
                'icon' => 'shield',
                'permission' => 'roles.view',
                'handler' => fn (string $query, int $page, int $perPage): array => $this->searchRoles($query, $page, $perPage),
            ],
            'activity' => [
                'label' => 'Activity',
                'icon' => 'activity',
                'permission' => 'activity.view',
                'handler' => fn (string $query, int $page, int $perPage): array => $this->searchActivity($query, $page, $perPage),
            ],
            'api-tokens' => [
                'label' => 'API Tokens',
                'icon' => 'key',
                'permission' => 'api_token.view',
                'handler' => fn (string $query, int $page, int $perPage): array => $this->searchApiTokens($query, $page, $perPage),
            ],
        ];
    }

    /**
     * @return array<string, array{label:string,icon:string,permission:string,handler:callable}>
     */
    private function allowedProviders(): array
    {
        return array_filter(
            $this->providers(),
            fn (array $provider): bool => $this->gate->allows($provider['permission'])
        );
    }

    /**
     * @return array{count:int,items:list<array<string,mixed>>}
     */
    private function searchUsers(string $query, int $page, int $perPage): array
    {
        $count = $this->countQuery(User::query(), $query, ['name', 'email']);
        $users = $this->searchQuery(User::query(), $query, ['name', 'email'])
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $items = [];
        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $role = $user->role();

            $items[] = [
                'title' => (string) $user->getAttribute('name'),
                'description' => (string) $user->getAttribute('email'),
                'url' => '/admin/users/' . $user->getKey(),
                'icon' => 'user',
                'badge' => $role !== null ? (string) $role->getAttribute('name') : '',
                'badge_tone' => 'accent',
            ];
        }

        return ['count' => $count, 'items' => $items];
    }

    /**
     * @return array{count:int,items:list<array<string,mixed>>}
     */
    private function searchRoles(string $query, int $page, int $perPage): array
    {
        $columns = ['name', 'slug', 'description'];
        $count = $this->countQuery(Role::query(), $query, $columns);
        $roles = $this->searchQuery(Role::query(), $query, $columns)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $items = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                continue;
            }

            $items[] = [
                'title' => (string) $role->getAttribute('name'),
                'description' => (string) $role->getAttribute('description'),
                'url' => '/admin/roles?q=' . rawurlencode((string) $role->getAttribute('slug')),
                'icon' => 'shield',
                'badge' => (string) $role->getAttribute('slug'),
            ];
        }

        return ['count' => $count, 'items' => $items];
    }

    /**
     * @return array{count:int,items:list<array<string,mixed>>}
     */
    private function searchActivity(string $query, int $page, int $perPage): array
    {
        $activity = new Activity();

        $countBuilder = Activity::query();
        $activity->scopeSearch($countBuilder->getBaseBuilder(), $query);
        $count = $countBuilder->count();

        $builder = Activity::query();
        $activity->scopeSearch($builder->getBaseBuilder(), $query);
        $activity->scopeSort($builder->getBaseBuilder(), 'created_at', 'desc');

        $items = [];
        foreach ($builder->limit($perPage)->offset(($page - 1) * $perPage)->get() as $row) {
            if (!$row instanceof Activity) {
                continue;
            }

            $description = trim((string) $row->getAttribute('description'));

            $items[] = [
                'title' => $description !== '' ? $description : (string) $row->getAttribute('action'),
                'description' => '',
                'url' => '/admin/activity?q=' . rawurlencode($query),
                'icon' => 'activity',
                'badge' => (string) $row->getAttribute('action'),
                'meta' => array_values(array_filter([
                    trim((string) $row->getAttribute('actor_name')),
                    trim((string) $row->getAttribute('created_at')),
                ])),
            ];
        }

        return ['count' => $count, 'items' => $items];
    }

    /**
     * @return array{count:int,items:list<array<string,mixed>>}
     */
    private function searchApiTokens(string $query, int $page, int $perPage): array
    {
        $columns = ['name', 'token_prefix'];
        $count = $this->countQuery(ApiToken::query(), $query, $columns);
        $tokens = $this->searchQuery(ApiToken::query(), $query, $columns)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $items = [];
        foreach ($tokens as $token) {
            if (!$token instanceof ApiToken) {
                continue;
            }

            $items[] = [
                'title' => (string) $token->getAttribute('name'),
                'description' => 'Prefix: ' . (string) $token->getAttribute('token_prefix'),
                'url' => '/admin/api-tokens?q=' . rawurlencode((string) $token->getAttribute('name')),
                'icon' => 'key',
                'badge' => ((int) $token->getAttribute('is_active')) === 1 ? 'Active' : 'Disabled',
                'badge_tone' => ((int) $token->getAttribute('is_active')) === 1 ? 'success' : 'neutral',
            ];
        }

        return ['count' => $count, 'items' => $items];
    }

    /**
     * @param list<string> $columns
     */
    private function countQuery(QueryBuilder $builder, string $query, array $columns): int
    {
        $this->search->applyLikeFilters($builder, $query, $columns);

        return $builder->count();
    }

    /**
     * @param list<string> $columns
     */
    private function searchQuery(QueryBuilder $builder, string $query, array $columns): QueryBuilder
    {
        $this->search->applyLikeFilters($builder, $query, $columns);

        return $builder;
    }
}
