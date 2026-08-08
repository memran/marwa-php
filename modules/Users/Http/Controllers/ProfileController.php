<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Support\ActivityTimeline;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\TwoFactorAuth;
use App\Modules\Users\Support\UserPasswordRules;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfileController extends Controller
{
    private const SESSION_2FA_SETUP_SECRET = 'users.2fa_setup_secret';

    public function __construct(
        private readonly AuthManager $auth,
        private readonly ActivityTimeline $activities,
        private readonly UserPasswordRules $passwordRules,
        private readonly TwoFactorAuth $twoFactor,
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
            'two_factor_mode' => $this->twoFactor->mode(),
            'two_factor_enabled' => $this->twoFactor->hasTwoFactor($user),
        ]);
    }

    public function updatePassword(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        $rules = $this->passwordRules->profileRules();
        $twoFactorRequired = $this->twoFactor->hasTwoFactor($user);

        if ($twoFactorRequired) {
            $rules['two_factor_code'] = 'required|string|max:16';
        }

        $validated = $this->validate($rules, $this->passwordRules->profileMessages(), request: $request);

        if (!$this->currentPasswordMatches($user, $validated)) {
            $this->withErrors(['current_password' => ['The current password you entered is incorrect.']])->withInput();

            return $this->redirect('/admin/profile');
        }

        if ($twoFactorRequired && !$this->twoFactor->verifyCode($user, trim((string) ($validated['two_factor_code'] ?? '')))) {
            $this->withErrors(['two_factor_code' => ['The authentication code is invalid or expired.']])->withInput();

            return $this->redirect('/admin/profile');
        }

        $newPassword = trim((string) ($validated['new_password'] ?? ''));
        $user->updatePasswordHash(password_hash($newPassword, PASSWORD_DEFAULT));
        $this->auth->refreshSessionFor($user);

        $this->flash('users.notice', 'Password updated successfully.');

        return $this->redirect('/admin/profile');
    }

    public function twoFactor(): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        $enabled = $this->twoFactor->hasTwoFactor($user);
        $secret = $this->session(self::SESSION_2FA_SETUP_SECRET, '');
        $account = (string) $user->getAttribute('email');
        $issuer = (string) config('app.name', 'MarwaPHP');
        $provisioningUri = is_string($secret) && $secret !== ''
            ? $this->twoFactor->provisioningUri($secret, $account, $issuer)
            : null;

        return $this->view('@users/two-factor', [
            'authUser' => $user,
            'two_factor_mode' => $this->twoFactor->mode(),
            'two_factor_enabled' => $enabled,
            'two_factor_secret' => is_string($secret) && $secret !== '' ? $secret : null,
            'provisioning_uri' => $provisioningUri,
            'issuer' => $issuer,
            'errors' => $this->session('errors', []),
            'old' => $this->session('_old_input', []),
            'notice' => $this->session('users.notice'),
        ]);
    }

    public function beginTwoFactorSetup(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        if ($this->twoFactor->isDisabled() || $this->twoFactor->hasTwoFactor($user)) {
            return $this->redirect('/admin/profile/2fa');
        }

        session()->set(self::SESSION_2FA_SETUP_SECRET, $this->twoFactor->generateSecret());

        return $this->redirect('/admin/profile/2fa');
    }

    public function confirmTwoFactorSetup(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        $secret = $this->session(self::SESSION_2FA_SETUP_SECRET, '');

        if (!is_string($secret) || $secret === '') {
            return $this->redirect('/admin/profile/2fa');
        }

        $validated = $this->validate([
            'current_password' => 'required|string',
            'code' => 'required|string|max:16',
        ], request: $request);

        if (!$this->currentPasswordMatches($user, $validated)) {
            $this->withErrors(['current_password' => ['Your current password is required to confirm two-factor authentication.']])->withInput();

            return $this->redirect('/admin/profile/2fa');
        }

        if (!$this->twoFactor->verifyPending($secret, trim((string) ($validated['code'] ?? '')))) {
            $this->withErrors(['code' => ['The authentication code is invalid or expired. Please try again.']])->withInput();

            return $this->redirect('/admin/profile/2fa');
        }

        if (method_exists($user, 'enableTwoFactor')) {
            $user->enableTwoFactor($secret);
        }

        session()->forget(self::SESSION_2FA_SETUP_SECRET);
        $this->flash('users.notice', 'Two-factor authentication is now enabled.');

        return $this->redirect('/admin/profile');
    }

    public function disableTwoFactor(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AdminAuthenticatableInterface) {
            return $this->redirect('/admin/login');
        }

        if (!$this->twoFactor->hasTwoFactor($user)) {
            return $this->redirect('/admin/profile/2fa');
        }

        $validated = $this->validate([
            'current_password' => 'required|string',
            'code' => 'required|string|max:16',
        ], request: $request);

        if (!$this->currentPasswordMatches($user, $validated)) {
            $this->withErrors(['current_password' => ['The current password you entered is incorrect.']])->withInput();

            return $this->redirect('/admin/profile/2fa');
        }

        if (!$this->twoFactor->verifyCode($user, trim((string) ($validated['code'] ?? '')))) {
            $this->withErrors(['code' => ['The authentication code is invalid or expired.']])->withInput();

            return $this->redirect('/admin/profile/2fa');
        }

        if (method_exists($user, 'disableTwoFactor')) {
            $user->disableTwoFactor();
        }

        $this->flash('users.notice', 'Two-factor authentication has been disabled.');

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
