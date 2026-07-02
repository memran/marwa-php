<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\PasswordResetMailer;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly PasswordResetMailer $passwordResetMailer,
    ) {
    }

    public function login(): ResponseInterface
    {
        if ($this->auth->check()) {
            return $this->redirect('/admin/');
        }

        $errors = session('errors', []);

        return $this->view('login', [
            'errors' => $errors,
            'login_error' => $this->loginErrorMessage($errors),
            'old' => session('_old_input', []),
            'notice' => session('auth.notice'),
        ]);
    }

    public function authenticate(ServerRequestInterface $request): ResponseInterface
    {
        $validated = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ], request: $request);

        $email = trim((string) ($validated['email'] ?? ''));
        $password = (string) ($validated['password'] ?? '');

        if (!$this->auth->attempt($email, $password, $this->ipAddress($request))) {
            if ($this->auth->lastFailureReason() === 'rate_limited') {
                $this->flash('auth.notice', 'Too many login attempts. Please try again later.');
            }

            $this->withErrors([
                'email' => 'The provided credentials are invalid.',
            ])->withInput([
                'email' => $email,
            ]);

            return $this->redirect('/admin/login');
        }

        $this->flash('auth.notice', 'Signed in successfully.');

        return $this->redirect('/admin/');
    }

    public function forgotPassword(): ResponseInterface
    {
        return $this->view('forgot-password', [
            'errors' => session('errors', []),
            'old' => session('_old_input', []),
            'notice' => session('auth.notice'),
        ]);
    }

    public function sendForgotPasswordLink(ServerRequestInterface $request): ResponseInterface
    {
        $validated = $this->validate([
            'email' => 'required|email',
        ], request: $request);

        $email = trim((string) ($validated['email'] ?? ''));
        $this->passwordResetMailer->sendPasswordResetEmail($email);

        $this->flash('auth.notice', 'If an admin account exists for that email, a recovery link has been sent.');

        return $this->redirect('/admin/forgot-password');
    }

    /**
     * @param array<string, string> $vars
     */
    public function resetPassword(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $token = $this->resolveResetToken($vars);

        return $this->view('reset-password', [
            'errors' => session('errors', []),
            'old' => session('_old_input', []),
            'token' => $token,
            'notice' => session('auth.notice'),
            'recovery_link' => session('auth.recovery_link'),
        ]);
    }

    /**
     * @param array<string, string> $vars
     */
    public function updatePassword(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $validated = $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ], request: $request);

        $token = $this->resolveResetToken($vars);
        $password = (string) ($validated['password'] ?? '');

        if (!$this->auth->resetPassword($token, $password)) {
            $this->withErrors([
                'token' => 'The recovery token is invalid or expired.',
            ])->withInput([
                'token' => $token,
            ]);

            return $this->redirect('/admin/reset-password/' . rawurlencode($token));
        }

        $this->flash('auth.notice', 'Password updated successfully.');

        return $this->redirect('/admin/login');
    }

    public function logout(): ResponseInterface
    {
        $this->auth->logout();
        $this->flash('auth.notice', 'Signed out successfully.');

        return $this->redirect('/admin/login');
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function resolveResetToken(array $vars = []): string
    {
        return trim((string) ($vars['token'] ?? ''));
    }

    private function ipAddress(ServerRequestInterface $request): string
    {
        $params = $request->getServerParams();

        return trim((string) ($params['REMOTE_ADDR'] ?? ''));
    }

    /**
     * @param mixed $errors
     */
    private function loginErrorMessage(mixed $errors): ?string
    {
        if (!is_array($errors)) {
            return null;
        }

        $messages = $errors['email'] ?? $errors['password'] ?? null;

        if (is_string($messages) && trim($messages) !== '') {
            return trim($messages);
        }

        if (is_array($messages)) {
            $message = reset($messages);

            if (is_string($message) && trim($message) !== '') {
                return trim($message);
            }
        }

        return null;
    }
}
