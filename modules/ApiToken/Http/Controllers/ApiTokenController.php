<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Http\Controllers;

use App\Modules\ApiToken\Support\ApiTokenFormData;
use App\Modules\ApiToken\Support\ApiTokenRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiTokenController extends Controller
{
    public function __construct(
        private readonly ApiTokenRepository $repository,
        private readonly ApiTokenFormData $forms,
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

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $validated = $this->validate($this->forms->rules(), $this->forms->messages(), request: $request);
        $payload = $this->forms->normalize($validated);

        if ($payload['invalid_ips'] !== []) {
            $this->withErrors([
                'allowed_ips' => [
                    'Allowed IPs must be valid IP addresses or IPv4 CIDR ranges: '
                    . implode(', ', $payload['invalid_ips']),
                ],
            ])->withInput([
                'name' => $payload['name'],
                'allowed_ips' => $payload['allowed_ips_text'],
                'rate_limit' => $payload['rate_limit'],
            ]);

            return $this->redirect('/admin/api-tokens/create');
        }

        try {
            $result = $this->repository->createToken(
                $payload['name'],
                $payload['allowed_ips'],
                $payload['rate_limit']
            );
            $token = $result['token'];
            $model = $result['model'];

            session()->set('api_token', $token);
            session()->set('api_token_name', $model->getAttribute('name'));
            $this->flash('success', 'API token created successfully.');

            return $this->redirect('/admin/api-tokens/show/' . $model->getKey());
        } catch (\Throwable) {
            $this->withErrors([
                'name' => 'Failed to create token. Please check the form and try again.',
            ])->withInput([
                'name' => $payload['name'],
                'allowed_ips' => $payload['allowed_ips_text'],
                'rate_limit' => $payload['rate_limit'],
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
}
