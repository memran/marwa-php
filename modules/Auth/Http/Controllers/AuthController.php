<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function login(): ResponseInterface
    {
        if ($this->auth->check()) {
            return $this->redirect('/admin/');
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
        return $this->view('@auth/forgot-password', $this->sharedViewData([
            'notice' => session('auth.notice'),
            'recovery_link' => session('auth.recovery_link'),
        ]));
    }

    public function sendForgotPasswordLink(): ResponseInterface
    {
        $validated = $this->validate([
            'email' => 'required|email',
        ]);

        $email = trim((string) ($validated['email'] ?? ''));
        $recoveryLink = $this->auth->createPasswordResetLink($email);

        if ($recoveryLink === null) {
            $this->withErrors([
                'email' => 'We could not prepare a recovery link for that address.',
            ])->withInput([
                'email' => $email,
            ]);

            return $this->redirect('/admin/forgot-password');
        }

        $this->flash('auth.notice', 'Recovery link created successfully.');
        $this->flash('auth.recovery_link', $recoveryLink);

        return $this->redirect('/admin/forgot-password');
    }

    /**
     * @param array<string, string> $vars
     */
    public function resetPassword(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $token = $this->resolveResetToken($request, $vars);

        return $this->view('@auth/reset-password', $this->sharedViewData([
            'token' => $token,
            'notice' => session('auth.notice'),
            'recovery_link' => session('auth.recovery_link'),
        ]));
    }

    /**
     * @param array<string, string> $vars
     */
    public function updatePassword(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $validated = $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $token = $this->resolveResetToken($request, $vars);
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
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function sharedViewData(array $extra = []): array
    {
        return array_replace([
            'errors' => session('errors', []),
            'old' => session('_old_input', []),
        ], $extra);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function resolveResetToken(ServerRequestInterface $request, array $vars = []): string
    {
        $token = (string) ($vars['token'] ?? '');

        if ($token !== '') {
            return trim($token);
        }

        $path = (string) $request->getUri()->getPath();
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return '';
        }

        return rawurldecode((string) end($segments));
    }
}
