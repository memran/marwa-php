<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserDeleteController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /**
     * @param array<string, mixed> $vars
     */
    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        if ($this->users->isLastAdminUser($user)) {
            $this->flash('users.notice', 'The last admin user cannot be deleted.');

            return $this->redirect('/admin/users');
        }

        $this->users->deleteUser($user);
        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }
}
