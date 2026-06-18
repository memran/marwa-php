<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use Psr\Http\Message\ServerRequestInterface;

final class UserIndexPage
{
    public function __construct(
        private readonly UserDataTable $userTable,
        private readonly UserNotice $notices,
    ) {}

    /**
     * @return array{stats:array{total:int,active:int,disabled:int,trashed:int},table:\App\Support\Datatables\Contracts\DataTableResultInterface,notice:?string}
     */
    public function viewData(ServerRequestInterface $request): array
    {
        return [
            'stats' => $this->stats(),
            'table' => $this->userTable->make($request)->paginate(per_page())->result(),
            'notice' => $this->notices->pull(),
        ];
    }

    /**
     * @return array{total:int,active:int,disabled:int,trashed:int}
     */
    private function stats(): array
    {
        $active = User::query()->where('is_active', '=', 1)->count();
        $disabled = User::query()->where('is_active', '=', 0)->count();
        $trashed = User::onlyTrashed()->count();

        return [
            'total' => $active + $disabled,
            'active' => $active,
            'disabled' => $disabled,
            'trashed' => $trashed,
        ];
    }
}
