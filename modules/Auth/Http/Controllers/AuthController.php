<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function login(): ResponseInterface
    {
        $this->ensureViewNamespace();

        if ($this->auth->check()) {
            return $this->redirect('/admin');
        }

        return $this->view('@auth/login', $this->sharedViewData([
            'notice' => session('auth.notice'),
        ]));
    }

    public function authenticate(): ResponseInterface
    {
        $validated = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $email = trim((string) ($validated['email'] ?? ''));
        $password = (string) ($validated['password'] ?? '');

        if (!$this->auth->attempt($email, $password)) {
            $this->withErrors([
                'email' => 'The provided credentials are invalid.',
            ])->withInput([
                'email' => $email,
            ]);

            return $this->redirect('/admin/login');
        }

        $this->flash('auth.notice', 'Signed in successfully.');

        return $this->redirect('/admin');
    }

    public function forgotPassword(): ResponseInterface
    {
        $this->ensureViewNamespace();

        return $this->view('@auth/forgot-password', $this->sharedViewData([
            'notice' => session('auth.notice'),
            'recovery_link' => session('auth.recovery_link'),
        ]));
    }

    public function sendForgotPasswordLink(): ResponseInterface
    {
        $this->ensureViewNamespace();

        $validated = $this->validate([
            'email' => 'required|email',
        ]);

        $email = trim((string) ($validated['email'] ?? ''));
        $link = $this->auth->createPasswordResetLink($email);

        if ($link === null) {
            $this->withErrors([
                'email' => 'No active admin account was found for that email address.',
            ])->withInput([
                'email' => $email,
            ]);

            return $this->redirect('/admin/forgot-password');
        }

        $this->flash('auth.notice', 'Recovery link generated.');
        $this->flash('auth.recovery_link', $link['url']);

        return $this->redirect('/admin/forgot-password');
    }

    public function resetPassword(): ResponseInterface
    {
        $this->ensureViewNamespace();

        $token = trim((string) $this->request('token', ''));

        return $this->view('@auth/reset-password', $this->sharedViewData([
            'token' => $token,
            'notice' => session('auth.notice'),
            'recovery_link' => session('auth.recovery_link'),
        ]));
    }

    public function updatePassword(): ResponseInterface
    {
        $this->ensureViewNamespace();

        $validated = $this->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $token = trim((string) ($validated['token'] ?? ''));
        $password = (string) ($validated['password'] ?? '');

        if (!$this->auth->resetPassword($token, $password)) {
            $this->withErrors([
                'token' => 'The reset link is invalid or expired.',
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

        return $this->redirect('/admin/login');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function sharedViewData(array $extra = []): array
    {
        return array_replace([
            'errors' => session(ValidationException::ERROR_BAG_KEY, []),
            'old' => session(ValidationException::OLD_INPUT_KEY, []),
        ], $extra);
    }

    private function ensureViewNamespace(): void
    {
        if (!app()->has(\Marwa\Framework\Views\View::class)) {
            return;
        }

        app()->view()->addNamespace('auth', dirname(__DIR__, 2) . '/resources/views');
    }
}
