<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\DataTable\DataTableRequestState;
use App\Support\DataTable\DataTableView;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;

final class UserBulkActions
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserActivityService $activity,
        private readonly AuthManager $auth,
        private readonly DataTableRequestState $requestState,
        private readonly DataTableView $tableView,
        private readonly UsersTableConfig $tableConfig,
    ) {}

    /**
     * @param array<string, mixed> $requestParams
     * @param list<string> $visibleColumns
     */
    public function bulkDelete(array $requestParams, array $visibleColumns): ResponseInterface
    {
        $selectedIds = $this->requestState->bulkSelectedIds();

        if ($selectedIds === []) {
            return $this->redirectWith($requestParams, $visibleColumns, 'Select at least one user before deleting.');
        }

        $users = $this->users->usersByIds($selectedIds);
        $counts = $this->deleteUsers($users, $this->auth->user());

        return $this->redirectWith(
            $requestParams,
            $visibleColumns,
            $this->bulkNoticeMessage('deleted', $counts['deleted'], $counts['skipped'])
        );
    }

    /**
     * @param list<User> $users
     * @return array{deleted:int,skipped:int}
     */
    private function deleteUsers(array $users, ?User $actor): array
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($this->isBulkProtected($user) || !empty($user->getAttribute('deleted_at'))) {
                $skipped++;
                continue;
            }

            $this->users->deleteUser($user);
            $this->activity->recordDeleted($user, $actor);
            $deleted++;
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * @param array<string, mixed> $requestParams
     * @param list<string> $visibleColumns
     */
    public function bulkStatus(array $requestParams, array $visibleColumns): ResponseInterface
    {
        $targetStatus = $this->resolveTargetStatus();

        if ($targetStatus === null) {
            return $this->redirectWith($requestParams, $visibleColumns, 'Choose a valid status before updating selected users.');
        }

        $selectedIds = $this->requestState->bulkSelectedIds();

        if ($selectedIds === []) {
            return $this->redirectWith($requestParams, $visibleColumns, 'Select at least one user before updating status.');
        }

        return $this->runBulkStatus($requestParams, $visibleColumns, $selectedIds, $targetStatus);
    }

    private function resolveTargetStatus(): ?UserStatus
    {
        $validTargets = [UserStatus::Active, UserStatus::Disabled];
        $target = UserStatus::tryFrom($this->requestState->bulkStatus());

        return ($target !== null && in_array($target, $validTargets, true)) ? $target : null;
    }

    /**
     * @param array<string, mixed> $requestParams
     * @param list<string> $visibleColumns
     * @param list<int> $selectedIds
     */
    private function runBulkStatus(
        array $requestParams,
        array $visibleColumns,
        array $selectedIds,
        UserStatus $targetStatus
    ): ResponseInterface {
        $users = $this->users->usersByIds($selectedIds);
        $counts = $this->applyStatusToUsers($users, $targetStatus->isActiveFlag(), $this->auth->user());

        return $this->redirectWith(
            $requestParams,
            $visibleColumns,
            $this->bulkNoticeMessage('updated', $counts['updated'], $counts['skipped'])
        );
    }

    /**
     * @param list<User> $users
     * @return array{updated:int,skipped:int}
     */
    private function applyStatusToUsers(array $users, int $afterStatus, ?User $actor): array
    {
        $updated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($this->shouldSkipForStatus($user, $afterStatus)) {
                $skipped++;
                continue;
            }

            $this->changeUserStatus($user, $afterStatus, $actor);
            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    private function shouldSkipForStatus(User $user, int $afterStatus): bool
    {
        return $this->isBulkProtected($user)
            || !empty($user->getAttribute('deleted_at'))
            || (int) $user->getAttribute('is_active') === $afterStatus;
    }

    private function changeUserStatus(User $user, int $afterStatus, ?User $actor): void
    {
        $beforeState = $this->users->userSnapshot($user);
        $afterState = [
            'name' => (string) $user->getAttribute('name'),
            'email' => User::normalizeEmail((string) $user->getAttribute('email')),
            'role_id' => (int) $user->getAttribute('role_id'),
            'is_active' => $afterStatus,
        ];
        $this->users->updateUser($user, $afterState, null);
        $this->activity->recordStatusChanged($user, $beforeState, $afterState, $actor);
    }

    private function isBulkProtected(User $user): bool
    {
        return $this->users->isActiveSessionUser($user, $this->auth)
            || $this->users->isLastAdminUser($user);
    }

    private function bulkNoticeMessage(string $verb, int $processed, int $skipped): string
    {
        if ($processed === 0) {
            return $skipped > 0
                ? 'No selected users could be ' . $verb . '.'
                : 'No selected users were changed.';
        }

        $message = ucfirst($verb) . ' ' . $processed . ' user' . ($processed === 1 ? '' : 's') . '.';

        if ($skipped > 0) {
            $message .= ' Skipped ' . $skipped . ' protected user' . ($skipped === 1 ? '' : 's') . '.';
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $requestParams
     * @param list<string> $visibleColumns
     */
    private function redirectWith(array $requestParams, array $visibleColumns, string $message): ResponseInterface
    {
        session()->flash('users.notice', $message);
        $url = $this->tableView->buildUsersUrl(
            $this->tableConfig,
            $this->requestState->resolve($requestParams),
            $visibleColumns
        );

        return Response::redirect($url);
    }
}
