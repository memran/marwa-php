<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use Psr\Http\Message\ServerRequestInterface;

final class UserIndexPage
{
    public function __construct(
        private readonly UserDataTable $userTable,
    ) {}

    /**
     * @return array{stats:array{total:int,active:int,disabled:int,trashed:int},table:\App\Support\Datatables\Contracts\DataTableResultInterface}
     */
    public function viewData(ServerRequestInterface $request): array
    {
        return [
            'stats' => $this->stats(),
            'table' => $this->userTable->make($request)->paginate(per_page())->result(),
            'notice' => $this->consumeFlash('users.notice'),
        ];
    }

    /**
     * @return array{total:int,active:int,disabled:int,trashed:int}
     */
    private function stats(): array
    {
        $users = User::collect();
        $activeUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 1
        );
        $disabledUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 0
        );
        $trashedUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) !== ''
        );

        return [
            'total' => $activeUsers->count() + $disabledUsers->count(),
            'active' => $activeUsers->count(),
            'disabled' => $disabledUsers->count(),
            'trashed' => $trashedUsers->count(),
        ];
    }

    private function consumeFlash(string $key): ?string
    {
        $value = session()->get($key);

        if (!is_string($value) || $value === '') {
            return null;
        }

        session()->forget($key);

        return $value;
    }
}
