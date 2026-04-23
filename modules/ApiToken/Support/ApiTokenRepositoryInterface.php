<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Support;

use App\Modules\ApiToken\Models\ApiToken;

interface ApiTokenRepositoryInterface
{
    public function findByToken(string $token): ?ApiToken;

    public function recordUsage(int $id): void;

    public function isRateLimited(ApiToken $token): bool;
}
