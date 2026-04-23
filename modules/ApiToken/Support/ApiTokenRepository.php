<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Support;

use App\Modules\ApiToken\Models\ApiToken;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Query\Builder;
use PDO;

final class ApiTokenRepository implements ApiTokenRepositoryInterface
{
    private const TOKEN_PREFIX = 'sk_';
    private const TOKEN_LENGTH = 40;

    public function __construct()
    {
    }

    public function generateToken(): string
    {
        return self::TOKEN_PREFIX . bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function getTokenPrefix(string $token): string
    {
        return substr($token, 0, 8);
    }

    public function findByToken(string $token): ?ApiToken
    {
        if (!app()->has(ConnectionManager::class)) {
            return null;
        }

        $hash = $this->hashToken($token);

        try {
            $row = ApiToken::newQuery()->getBaseBuilder()
                ->where('token_hash', '=', $hash)
                ->where('is_active', '=', true)
                ->first();

            if ($row === null) {
                return null;
            }

            return ApiToken::newInstance(is_array($row) ? $row : (array) $row, true);
        } catch (\Throwable) {
            return null;
        }
    }

    public function createToken(
        string $name,
        array $allowedIps = [],
        int $rateLimit = 60,
    ): array {
        $token = $this->generateToken();
        $hash = $this->hashToken($token);
        $prefix = $this->getTokenPrefix($token);
        $now = date('Y-m-d H:i:s');

        $apiToken = ApiToken::create([
            'name' => $name,
            'token_hash' => $hash,
            'token_prefix' => $prefix,
            'allowed_ips' => json_encode($allowedIps),
            'rate_limit' => $rateLimit,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'token' => $token,
            'model' => $apiToken,
        ];
    }

    public function all(): array
    {
        if (!app()->has(ConnectionManager::class)) {
            return [];
        }

        try {
            $rows = ApiToken::newQuery()->getBaseBuilder()
                ->orderBy('created_at', 'desc')
                ->get();

            return array_map(
                static fn (array|object $row): ApiToken => ApiToken::newInstance(
                    is_array($row) ? $row : (array) $row,
                    true
                ),
                $rows
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function findById(int $id): ?ApiToken
    {
        $row = ApiToken::newQuery()->getBaseBuilder()
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : ApiToken::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function revoke(int $id): bool
    {
        $token = $this->findById($id);

        if (!$token instanceof ApiToken) {
            return false;
        }

        $token->deactivate();

        return true;
    }

    public function toggle(int $id): bool
    {
        $token = $this->findById($id);

        if (!$token instanceof ApiToken) {
            return false;
        }

        if ($token->isActive()) {
            $token->deactivate();
        } else {
            $token->activate();
        }

        return true;
    }

    public function recordUsage(int $id): void
    {
        $token = $this->findById($id);

        if ($token instanceof ApiToken) {
            $token->touchLastUsed();
        }
    }

    public function isRateLimited(ApiToken $token): bool
    {
        return !throttle(
            'api-token:' . $token->getKey(),
            $token->getRateLimit(),
            60
        );
    }
}
