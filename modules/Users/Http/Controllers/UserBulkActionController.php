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
        /** @var list<int|string> $ids */
        $ids = (array) ($request->getParsedBody()['ids'] ?? []);
        $result = $this->users->bulkDelete($ids);

        $this->flash('users.notice', $this->formatBulkNotice(
            $result['deleted'],
            $result['skipped'],
            'deleted',
            static fn (int $count): string => $count . ' user' . ($count !== 1 ? 's' : '') . ' deleted.',
        ));

        return $this->redirect('/admin/users');
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<int|string> $ids */
        $ids = (array) ($request->getParsedBody()['ids'] ?? []);
        $status = strtolower(trim((string) ($request->getParsedBody()['bulk_status'] ?? '')));

        if (!in_array($status, ['active', 'disabled'], true)) {
            $this->flash('users.notice', 'Invalid status value.');

            return $this->redirect('/admin/users');
        }

        $result = $this->users->bulkStatus($ids, $status === 'active');

        $this->flash('users.notice', $this->formatBulkNotice(
            $result['updated'],
            $result['skipped'],
            $status,
            static fn (int $count): string => $count . ' user' . ($count !== 1 ? 's' : '') . ' set to ' . $status . '.',
        ));

        return $this->redirect('/admin/users');
    }

    /**
     * @param callable(int): string $successMessage
     */
    private function formatBulkNotice(int $successCount, int $skippedCount, string $context, callable $successMessage): string
    {
        $parts = [];

        if ($successCount > 0) {
            $parts[] = $successMessage($successCount);
        }

        if ($skippedCount > 0) {
            $parts[] = $skippedCount . ' skipped (protected or not found).';
        }

        return implode(' ', $parts);
    }
}
