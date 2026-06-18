<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserUpdateController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserFormData $forms,
    ) {}

    /**
     * @param array<string, mixed> $vars
     */
    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $user = $this->users->findById($id);

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        $validated = $this->validate($this->forms->rules(true), $this->forms->messages(), request: $request);
        $payload = $this->forms->normalize($validated);

        if (($duplicateEmailError = $this->forms->duplicateEmailError($payload['email'], $user)) !== null) {
            $this->withErrors(['email' => [$duplicateEmailError]])->withInput();

            return $this->redirect('/admin/users/' . $id . '/edit');
        }

        $this->users->updateUser($user, $payload, $payload['password'] !== '' ? $payload['password'] : null);
        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }
}
