<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Models;

use Marwa\Framework\Database\Model;

final class ApiToken extends Model
{
    protected static ?string $table = 'api_tokens';
    protected static bool $softDeletes = true;

    protected static array $fillable = [
        'name',
        'token_hash',
        'token_prefix',
        'allowed_ips',
        'rate_limit',
        'is_active',
        'created_by',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    protected static array $casts = [
        'allowed_ips' => 'json',
        'rate_limit' => 'int',
        'is_active' => 'int',
        'created_by' => 'int',
    ];

    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active');
    }

    public function getAllowedIps(): array
    {
        $ips = $this->getAttribute('allowed_ips');

        if (is_string($ips)) {
            $ips = json_decode($ips, true);
        }

        return is_array($ips) ? $ips : [];
    }

    public function getRateLimit(): int
    {
        return (int) $this->getAttribute('rate_limit');
    }

    public function getTokenPrefix(): string
    {
        return (string) $this->getAttribute('token_prefix');
    }

    public function getMaskedToken(): string
    {
        $prefix = $this->getTokenPrefix();

        return substr($prefix, 0, 3) . '...' . substr($prefix, -4);
    }

    public function hasIpRestriction(): bool
    {
        return count($this->getAllowedIps()) > 0;
    }

    public function isIpAllowed(string $ip): bool
    {
        $allowedIps = $this->getAllowedIps();

        if (empty($allowedIps)) {
            return true;
        }

        foreach ($allowedIps as $allowed) {
            if ($this->ipMatches($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatches(string $ip, string $pattern): bool
    {
        if (str_contains($pattern, '/')) {
            return $this->cidrContains($pattern, $ip);
        }

        return $ip === $pattern;
    }

    private function cidrContains(string $cidr, string $ip): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = ~((1 << (32 - $mask)) - 1);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    public function touchLastUsed(): void
    {
        $this->fill(['last_used_at' => date('Y-m-d H:i:s')]);
        $this->save();
    }

    public function deactivate(): void
    {
        $this->fill(['is_active' => false]);
        $this->save();
    }

    public function activate(): void
    {
        $this->fill(['is_active' => true]);
        $this->save();
    }
}
