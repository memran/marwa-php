<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserBulkActionController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<string> $ids */
        $ids = (array) ($request->getParsedBody()['ids'] ?? []);
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $userId = (int) $id;
            if ($userId <= 0) {
                continue;
            }

            $user = $this->users->findById($userId);
            if ($user === null || $this->users->isLastAdminUser($user)) {
                $skipped++;
                continue;
            }

            $this->users->deleteUser($user);
            $deleted++;
        }

        $parts = [];
        if ($deleted > 0) {
            $parts[] = $deleted . ' user' . ($deleted !== 1 ? 's' : '') . ' deleted.';
        }
        if ($skipped > 0) {
            $parts[] = $skipped . ' skipped (protected or not found).';
        }

        $this->flash('users.notice', implode(' ', $parts));

        return $this->redirect('/admin/users');
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<string> $ids */
        $ids = (array) ($request->getParsedBody()['ids'] ?? []);
        $status = strtolower(trim((string) ($request->getParsedBody()['bulk_status'] ?? '')));

        if (!in_array($status, ['active', 'disabled'], true)) {
            $this->flash('users.notice', 'Invalid status value.');
            return $this->redirect('/admin/users');
        }

        $isActive = $status === 'active' ? 1 : 0;
        $updated = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $userId = (int) $id;
            if ($userId <= 0) {
                continue;
            }

            $user = $this->users->findById($userId);
            if ($user === null || $this->users->isLastAdminUser($user)) {
                $skipped++;
                continue;
            }

            $user->setAttribute('is_active', $isActive);
            $user->save();
            $updated++;
        }

        $parts = [];
        if ($updated > 0) {
            $parts[] = $updated . ' user' . ($updated !== 1 ? 's' : '') . ' set to ' . $status . '.';
        }
        if ($skipped > 0) {
            $parts[] = $skipped . ' skipped (protected or not found).';
        }

        $this->flash('users.notice', implode(' ', $parts));

        return $this->redirect('/admin/users');
    }
}
