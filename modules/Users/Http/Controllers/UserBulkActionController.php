<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserNotice;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserBulkActionController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserNotice $notices,
        private readonly AuthManager $auth,
    ) {}

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<int|string> $ids */
        $ids = (array) $this->input('ids', []);
        $actor = $this->auth->user();
        $result = $this->users->bulkDelete($ids, $actor instanceof User ? $actor : null);

        $this->flash('users.notice', $this->notices->bulkResult(
            $result['deleted'],
            $result['skipped'],
            static fn (int $count): string => $count . ' user' . ($count !== 1 ? 's' : '') . ' deleted.',
        ));

        return $this->redirect('/admin/users');
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<int|string> $ids */
        $ids = (array) $this->input('ids', []);
        $status = strtolower(trim((string) $this->input('bulk_status', '')));

        if (!in_array($status, ['active', 'disabled'], true)) {
            $this->flash('users.notice', 'Invalid status value.');

            return $this->redirect('/admin/users');
        }

        $actor = $this->auth->user();
        $result = $this->users->bulkStatus($ids, $status === 'active', $actor instanceof User ? $actor : null);

        $this->flash('users.notice', $this->notices->bulkResult(
            $result['updated'],
            $result['skipped'],
            static fn (int $count): string => $count . ' user' . ($count !== 1 ? 's' : '') . ' set to ' . $status . '.',
        ));

        return $this->redirect('/admin/users');
    }
}
