<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Models\Activity;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\Pagination;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Pagination $pagination,
    ) {}

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activityPageData = $this->recentActivities($user, $activityPage);

        return $this->view('@users/profile', [
            'authUser' => $user,
            'errors' => $this->session('errors', []),
            'old' => $this->session('_old_input', []),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activityPageData['data'],
            'activity_total' => $activityPageData['pagination']['total'],
            'activity_pagination' => $this->pagination->viewData(
                $activityPageData['pagination'],
                '/admin/profile',
                [
                    'tab' => 'activity',
                ],
                'activity_page'
            ),
        ]);
    }

    public function updatePassword(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $body = $request->getParsedBody();
        $input = is_array($body) ? $body : [];

        $currentPassword = trim((string) ($input['current_password'] ?? ''));
        $newPassword = trim((string) ($input['new_password'] ?? ''));
        $newPasswordConfirmation = trim((string) ($input['new_password_confirmation'] ?? ''));

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'][] = 'Your current password is required.';
        } elseif (!password_verify($currentPassword, (string) $user->getPasswordHash())) {
            $errors['current_password'][] = 'The current password you entered is incorrect.';
        }

        if ($newPassword === '') {
            $errors['new_password'][] = 'The new password field is required.';
        } elseif (mb_strlen($newPassword) < 8) {
            $errors['new_password'][] = 'The new password must be at least 8 characters.';
        }

        if ($newPasswordConfirmation === '') {
            $errors['new_password_confirmation'][] = 'Please confirm your new password.';
        } elseif ($newPassword !== '' && $newPassword !== $newPasswordConfirmation) {
            $errors['new_password_confirmation'][] = 'The new password confirmation does not match.';
        }

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($input);

            return $this->redirect('/admin/profile');
        }

        $user->setAttribute('password', password_hash($newPassword, PASSWORD_DEFAULT));
        $user->saveOrFail();

        $this->flash('users.notice', 'Password updated successfully.');

        return $this->redirect('/admin/profile');
    }

    /**
     * @return array{
     *     data:list<Activity>,
     *     pagination:array{total:int,per_page:int,current_page:int,last_page:int}
     * }
     */
    private function recentActivities(User $user, int $page = 1, int $perPage = 5): array
    {
        try {
            $builder = Activity::newQuery()->getBaseBuilder()
                ->where('actor_email', '=', $user->getAttribute('email'))
                ->orderBy('created_at', 'desc')
                ->paginate(max(1, $perPage), max(1, $page));
        } catch (\Throwable) {
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => max(1, $perPage),
                    'current_page' => max(1, $page),
                    'last_page' => 1,
                ],
            ];
        }

        $rows = $builder['data'] ?? [];

        return [
            'data' => array_values(array_filter(array_map(
            static fn (array|object $row): Activity => Activity::newInstance(is_array($row) ? $row : (array) $row, true),
            is_array($rows) ? $rows : []
        ), static fn (Activity $activity): bool => $activity instanceof Activity)),
            'pagination' => [
                'total' => (int) ($builder['total'] ?? 0),
                'per_page' => (int) ($builder['per_page'] ?? max(1, $perPage)),
                'current_page' => (int) ($builder['current_page'] ?? max(1, $page)),
                'last_page' => (int) ($builder['last_page'] ?? 1),
            ],
        ];
    }
}
