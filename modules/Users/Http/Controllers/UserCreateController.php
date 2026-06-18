<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserFormData;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class UserCreateController extends Controller
{
    public function __construct(
        private readonly UserFormData $forms,
    ) {}

    public function create(): ResponseInterface
    {
        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
            'user' => null,
        ]));
    }
}
