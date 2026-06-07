<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Http\Controllers\Concerns\RendersUserFormTrait;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserEditController extends Controller
{
    use RendersUserFormTrait;

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserFormData $forms,
    ) {}

    /**
     * @param array<string, mixed> $vars
     */
    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        return $this->view('@users/form', $this->userFormViewData($this->forms, [
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }
}
