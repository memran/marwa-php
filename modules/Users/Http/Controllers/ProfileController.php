<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Support\ActivityTimeline;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ActivityTimeline $activities,
    ) {}

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activity = $this->activities->actorEmail(
            (string) $user->getAttribute('email'),
            '/admin/profile',
            $activityPage,
            5,
            ['tab' => 'activity']
        );

        return $this->view('@users/profile', [
            'authUser' => $user,
            'errors' => $this->session('errors', []),
            'old' => $this->session('_old_input', []),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activity['data'],
            'activity_total' => $activity['total'],
            'activity_pagination' => $activity['pagination'],
        ]);
    }

    public function updatePassword(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
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

        $user->updatePasswordHash(password_hash($newPassword, PASSWORD_DEFAULT));

        $this->flash('users.notice', 'Password updated successfully.');

        return $this->redirect('/admin/profile');
    }

}
