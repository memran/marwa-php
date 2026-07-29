<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use Psr\Http\Message\ServerRequestInterface;

final class RoleIndexPage
{
    public function __construct(
        private readonly RoleDataTable $roleTable,
    ) {}

    /**
     * @return array{table:\App\Support\Datatables\Contracts\DataTableResultInterface}
     */
    public function viewData(ServerRequestInterface $request): array
    {
        return [
            'table' => $this->roleTable->make($request)->paginate(per_page())->result(),
        ];
    }
}
