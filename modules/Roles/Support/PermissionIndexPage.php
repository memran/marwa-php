<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use Psr\Http\Message\ServerRequestInterface;

final class PermissionIndexPage
{
    public function __construct(
        private readonly PermissionDataTable $permissionTable,
    ) {}

    /**
     * @return array{table:\App\Support\Datatables\Contracts\DataTableResultInterface}
     */
    public function viewData(ServerRequestInterface $request): array
    {
        return [
            'table' => $this->permissionTable->make($request)->paginate(per_page())->result(),
        ];
    }
}
