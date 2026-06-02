<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Support\AdminSearch;
use App\Modules\Users\Models\User;

final class UserListing
{
    public function __construct(
        private readonly AdminSearch $search,
    ) {}

    /**
     * @return array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginatedUsers(
        string $query,
        int $page,
        ?int $perPage = null,
        UserStatus $status = UserStatus::All,
        string $sort = 'created_at',
        string $direction = 'desc'
    ): array {
        $page = max(1, $page);
        $perPage = max(1, (int) ($perPage ?? config('settings.lifecycle.pagination.default_per_page', config('pagination.default_per_page', 10))));
        $builder = $this->buildListingBuilder($query, $status, $sort, $direction);
        $pageData = $builder->paginate($perPage, $page);
        $pageData['data'] = $this->hydrateUsers($pageData['data']);

        return $pageData;
    }

    /**
     * @return list<User>
     */
    public function listUsers(
        string $query,
        UserStatus $status = UserStatus::All,
        string $sort = 'created_at',
        string $direction = 'desc'
    ): array {
        return $this->hydrateUsers($this->buildListingBuilder($query, $status, $sort, $direction)->get());
    }

    private function applyStatusFilter(object $builder, UserStatus $status): void
    {
        $activeFlag = $status->isActiveFlag();
        if ($activeFlag !== null) {
            $builder->whereNull('deleted_at')->where('is_active', '=', $activeFlag);
            return;
        }

        if ($status === UserStatus::Trashed) {
            $builder->whereNotNull('deleted_at');
        }
    }

    private function applySort(object $builder, string $sort, string $direction): void
    {
        $column = match ($sort) {
            'name' => 'name',
            'email' => 'email',
            'role' => 'role_id',
            'last_login' => 'last_login_at',
            default => 'created_at',
        };

        $builder->orderBy($column, $direction);
    }

    private function buildListingBuilder(string $query, UserStatus $status, string $sort, string $direction): object
    {
        $builder = User::newQuery()->getBaseBuilder();
        $query = trim($query);
        $sort = trim($sort);
        $direction = strtolower(trim($direction)) === 'asc' ? 'asc' : 'desc';

        $this->search->applyLikeFilters($builder, $query, ['name', 'email']);
        $this->applyStatusFilter($builder, $status);
        $this->applySort($builder, $sort, $direction);

        return $builder;
    }

    /**
     * @param array<int, array<string, mixed>|object> $rows
     * @return list<User>
     */
    private function hydrateUsers(array $rows): array
    {
        $users = array_map(
            static fn (array|object $row): User => User::newInstance(is_array($row) ? $row : (array) $row, true),
            $rows
        );

        if ($users !== []) {
            $users[0]->roleRelation()->eagerLoad($users, 'roleRelation');
        }

        return $users;
    }
}
