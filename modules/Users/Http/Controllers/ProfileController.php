<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfileController extends Controller
{
    public function __construct(
        protected readonly UserRepository $users,
        protected readonly AuthManager $auth,
    ) {}

    public function index(): ResponseInterface
    {
        $currentUser = $this->auth->user();

        if ($currentUser === null) {
            return $this->redirect('/admin/login');
        }

        return $this->view('@users/profile', [
            'user' => $currentUser,
            'is_self' => true,
        ]);
    }

    public function edit(): ResponseInterface
    {
        $currentUser = $this->auth->user();

        if ($currentUser === null) {
            return $this->redirect('/admin/login');
        }

        return $this->view('@users/profile-form', [
            'mode' => 'edit',
            'title' => 'Edit profile',
            'action' => '/admin/profile',
            'submit_label' => 'Save changes',
            'user' => $currentUser,
        ]);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $currentUser = $this->auth->user();

        if ($currentUser === null) {
            return $this->redirect('/admin/login');
        }

        $validated = $this->validate([
            'name' => 'required|min:2|max:255',
            'email' => 'required|email',
            'password' => 'nullable|min:8|confirmed',
            'current_password' => 'required_with:password|min:8',
        ], [], [], $request);

        if (!password_verify((string) $validated['current_password'], (string) $currentUser->getAttribute('password'))) {
            $this->withErrors([
                'current_password' => ['The current password is incorrect.'],
            ])->withInput([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            return $this->redirect('/admin/profile/edit');
        }

        $email = $this->users->normalizeEmail((string) $validated['email']);
        $duplicate = $this->users->findDuplicateUserByEmail($email, (int) $currentUser->getKey());

        if ($duplicate !== null) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $validated['name'],
                'email' => $email,
            ]);

            return $this->redirect('/admin/profile/edit');
        }

        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => $email,
        ];
        $password = $validated['password'] !== null ? (string) $validated['password'] : null;

        $this->users->updateUser($currentUser, $afterState, $password, $currentUser);
        $this->flash('profile.notice', 'Profile updated successfully.');

        $this->withErrors([])->withInput([]);

        return $this->redirect('/admin/profile');
    }
}