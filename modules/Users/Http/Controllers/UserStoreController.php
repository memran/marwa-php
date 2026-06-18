<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserStoreController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserFormData $forms,
    ) {}

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $validated = $this->validate($this->forms->rules(), $this->forms->messages(), request: $request);
        $payload = $this->forms->normalize($validated);

        if (($duplicateEmailError = $this->forms->duplicateEmailError($payload['email'])) !== null) {
            $this->withErrors(['email' => [$duplicateEmailError]])->withInput();

            return $this->redirect('/admin/users/create');
        }

        $this->users->createUser($payload);
        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }
}
