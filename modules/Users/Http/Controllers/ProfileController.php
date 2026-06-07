<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Models\Activity;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activityPageData = $this->activityPageData(
            (string) $user->getAttribute('email'),
            $activityPage
        );

        return $this->view('@users/profile', [
            'authUser' => $user,
            'errors' => $this->session('errors', []),
            'old' => $this->session('_old_input', []),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activityPageData['data'],
            'activity_total' => $activityPageData['pagination']['total'],
            'activity_pagination' => pagination_view_data(
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
     * @return array{data:list<Activity>,pagination:array{total:int,per_page:int,current_page:int,last_page:int}}
     */
    private function activityPageData(string $email, int $page, int $perPage = 5): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        try {
            $activity = new Activity();
            $query = Activity::query();
            $builder = $query->getBaseBuilder();

            $activity->scopeActorEmail($builder, $email);
            $activity->scopeSort($builder, 'created_at', 'desc');

            $pageData = $query->paginate($perPage, $page);
        } catch (\Throwable) {
            $pageData = [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1,
            ];
        }

        return [
            'data' => $pageData['data'],
            'pagination' => [
                'total' => (int) $pageData['total'],
                'per_page' => (int) $pageData['per_page'],
                'current_page' => (int) $pageData['current_page'],
                'last_page' => (int) $pageData['last_page'],
            ],
        ];
    }
}
