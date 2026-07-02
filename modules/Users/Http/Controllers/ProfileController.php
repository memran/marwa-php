<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Support\ActivityTimeline;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Support\UserPasswordRules;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ActivityTimeline $activities,
        private readonly UserPasswordRules $passwordRules,
    ) {}

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        if (method_exists($user, 'loadMissing')) {
            $user->loadMissing('roleRelation', 'roleRelation.permissionsRelation');
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

        $validated = $this->validate($this->passwordRules->profileRules(), $this->passwordRules->profileMessages(), request: $request);

        if (!$this->currentPasswordMatches($user, $validated)) {
            $this->withErrors(['current_password' => ['The current password you entered is incorrect.']])->withInput();

            return $this->redirect('/admin/profile');
        }

        $newPassword = trim((string) ($validated['new_password'] ?? ''));
        $user->updatePasswordHash(password_hash($newPassword, PASSWORD_DEFAULT));
        $this->auth->refreshSessionFor($user);

        $this->flash('users.notice', 'Password updated successfully.');

        return $this->redirect('/admin/profile');
    }

    /**
     * @param array<string, mixed> $input
     */
    private function currentPasswordMatches(AdminAuthenticatableInterface $user, array $input): bool
    {
        $currentPassword = trim((string) ($input['current_password'] ?? ''));

        if ($currentPassword === '') {
            return false;
        }

        return password_verify($currentPassword, (string) $user->getPasswordHash());
    }

}
