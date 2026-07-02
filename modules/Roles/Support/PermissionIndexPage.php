<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use Psr\Http\Message\ServerRequestInterface;

final class PermissionIndexPage
{
    public function __construct(
        private readonly PermissionDataTable $permissionTable,
        private readonly RoleModuleNotice $notice,
    ) {}

    /**
     * @return array{table:\App\Support\Datatables\Contracts\DataTableResultInterface,notice:?string}
     */
    public function viewData(ServerRequestInterface $request): array
    {
        return [
            'table' => $this->permissionTable->make($request)->paginate(per_page())->result(),
            'notice' => $this->notice->pull('permissions.notice'),
        ];
    }
}
