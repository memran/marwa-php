<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Mail\PasswordResetMail;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Router\Http\Input;
use Marwa\Support\Security;
use Psr\Http\Message\ResponseInterface;

final class AuthController extends Controller
{
    public function __construct(
        private AuthManager $auth
    ) {}

    public function showLoginForm(): ResponseInterface
    {
        return $this->view('@auth/login', [
            'title' => 'Sign in',
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function login(): ResponseInterface
    {
        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'remember' => 'sometimes|boolean',
        ]);

        $user = $this->auth->attempt(
            (string) $data['email'],
            (string) $data['password']
        );

        if (!$user instanceof User) {
            $this->withInput([
                'email' => $data['email'],
            ]);
            $this->withErrors([
                'email' => 'The provided credentials are invalid.',
            ]);

            return $this->back(303);
        }

        $response = $this->redirect($this->auth->consumeIntendedUrl('/admin'), 303);
        $remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($remember) {
            $response = $response->withAddedHeader('Set-Cookie', $this->auth->rememberCookieHeader($user));
        } else {
            $response = $response->withAddedHeader('Set-Cookie', $this->auth->forgetRememberCookieHeader());
        }

        return $response;
    }

    public function showRegisterForm(): ResponseInterface
    {
        return $this->view('@auth/register', [
            'title' => 'Create account',
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function register(): ResponseInterface
    {
        $data = $this->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->auth->register(
            (string) $data['name'],
            (string) $data['email'],
            (string) $data['password']
        );

        return $this->redirect('/admin', 303)
            ->withAddedHeader('Set-Cookie', $this->auth->forgetRememberCookieHeader());
    }

    public function showForgotPasswordForm(): ResponseInterface
    {
        return $this->view('@auth/forgot-password', [
            'title' => 'Reset password',
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function sendPasswordResetLink(): ResponseInterface
    {
        $data = $this->validate([
            'email' => 'required|email',
        ]);

        $payload = $this->auth->createPasswordResetToken((string) $data['email']);

        if (is_array($payload)) {
            $mailer = app(MailerInterface::class);

            if ($mailer->configuration()['enabled']) {
                (new PasswordResetMail($payload + [
                    'to' => [(string) $payload['email'] => (string) $payload['name']],
                ]))->send();
            } else {
                $this->flash('auth_reset_link', (string) $payload['url']);
            }
        }

        $this->flash('auth_notice', 'If the account exists, a reset link has been prepared.');

        return $this->back(303);
    }

    public function showResetPasswordForm(string $token = ''): ResponseInterface
    {
        return $this->view('@auth/reset-password', [
            'title' => 'Choose a new password',
            'token' => $token,
            'email' => (string) Input::get('email', ''),
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function resetPassword(): ResponseInterface
    {
        $data = $this->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $ok = $this->auth->resetPassword(
            (string) $data['email'],
            (string) $data['token'],
            (string) $data['password']
        );

        return $ok
            ? $this->redirect('/auth/login', 303)
            : $this->back(303);
    }

    public function profile(): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return $this->redirect('/auth/login', 303);
        }

        return $this->view('@auth/profile', [
            'title' => 'Your profile',
            'user' => $user,
            'roles' => $user->relationLoaded('roles') && is_array($user->getRelation('roles'))
                ? $user->getRelation('roles')
                : [],
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function showChangePasswordForm(): ResponseInterface
    {
        return $this->view('@auth/change-password', [
            'title' => 'Change password',
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function changePassword(): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return $this->redirect('/auth/login', 303);
        }

        $data = $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!$this->auth->changePassword($user, (string) $data['current_password'], (string) $data['password'])) {
            $this->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);

            return $this->back(303);
        }

        return $this->redirect('/admin/profile', 303);
    }

    public function logout(): ResponseInterface
    {
        $user = $this->auth->user();
        $response = $this->redirect('/', 303);

        if ($user instanceof User) {
            $response = $response->withAddedHeader('Set-Cookie', $this->auth->forgetRememberCookieHeader());
        }

        $this->auth->logout();

        return $response;
    }
}
