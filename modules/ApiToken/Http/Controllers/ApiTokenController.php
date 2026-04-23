<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Http\Controllers;

use App\Modules\ApiToken\Support\ApiTokenRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiTokenController extends Controller
{
    public function __construct(
        private readonly ApiTokenRepository $repository
    ) {}

    public function index(): ResponseInterface
    {
        $tokens = $this->repository->all();

        return $this->view('@api_token/index', [
            'tokens' => $tokens,
            'errors' => session('errors', []),
            'old' => session('_old_input', []),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->view('@api_token/create', [
            'errors' => session('errors', []),
            'old' => session('_old_input', []),
        ]);
    }

    public function store(): ResponseInterface
    {
        $validated = $this->validate([
            'name' => 'required|string|max:100',
            'allowed_ips' => 'nullable|string',
            'rate_limit' => 'required|integer|min:1|max:10000',
        ]);

        $name = trim((string) ($validated['name'] ?? ''));
        $rateLimit = (int) ($validated['rate_limit'] ?? 60);

        $allowedIpsText = trim((string) ($validated['allowed_ips'] ?? ''));
        $allowedIps = [];

        if ($allowedIpsText !== '') {
            $lines = explode("\n", $allowedIpsText);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    if (filter_var($line, FILTER_VALIDATE_IP) !== false) {
                        $allowedIps[] = $line;
                    } elseif (preg_match('/^[\d.]+\/\d+$/', $line)) {
                        $allowedIps[] = $line;
                    }
                }
            }
        }

        try {
            $result = $this->repository->createToken($name, $allowedIps, $rateLimit);
            $token = $result['token'];
            $model = $result['model'];

            session()->set('api_token', $token);
            session()->set('api_token_name', $model->getAttribute('name'));
            $this->flash('success', 'API token created successfully.');

            return $this->redirect('/admin/api-tokens/show/' . $model->getKey());
        } catch (\Throwable $e) {
            $this->withErrors([
                'name' => 'Failed to create token. Please check the form and try again.',
            ])->withInput([
                'name' => $name,
                'allowed_ips' => $allowedIpsText,
                'rate_limit' => $rateLimit,
            ]);

            return $this->redirect('/admin/api-tokens/create');
        }
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $token = $this->repository->findById($id);

        if ($token === null) {
            return $this->redirect('/admin/api-tokens');
        }

        $displayToken = session('api_token');
        $tokenName = session('api_token_name');

        if ($displayToken !== null) {
            session()->forget('api_token');
            session()->forget('api_token_name');
        }

        return $this->view('@api_token/show', [
            'token' => $token,
            'display_token' => $displayToken,
            'token_name' => $tokenName,
            'success' => session('success'),
        ]);
    }

    public function toggle(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $this->repository->toggle($id);

        return $this->redirect('/admin/api-tokens');
    }

    public function revoke(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $this->repository->revoke($id);

        $this->flash('success', 'API token revoked successfully.');

        return $this->redirect('/admin/api-tokens');
    }
}
