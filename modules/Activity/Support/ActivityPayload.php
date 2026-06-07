<?php

declare(strict_types=1);

namespace App\Modules\Activity\Support;

use App\Modules\Users\Models\User;
use Psr\Http\Message\ServerRequestInterface;

final class ActivityPayload
{
    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    public static function actorAction(
        string $action,
        string $description,
        ?User $actor = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $details = []
    ): array {
        return [
            'action' => trim($action),
            'description' => trim($description),
            'subject_type' => self::stringValue($subjectType),
            'subject_id' => $subjectId,
            'details' => self::encodeDetails($details),
        ] + self::actorContext($actor) + self::requestContext();
    }

    /**
     * @return array{actor_name: ?string, actor_email: ?string}
     */
    private static function actorContext(?User $actor): array
    {
        return [
            'actor_name' => $actor instanceof User ? self::stringValue($actor->getAttribute('name')) : null,
            'actor_email' => $actor instanceof User ? self::stringValue($actor->getAttribute('email')) : null,
        ];
    }

    /**
     * @return array{ip_address: ?string, user_agent: ?string}
     */
    private static function requestContext(): array
    {
        try {
            if (!app()->has(ServerRequestInterface::class)) {
                return [
                    'ip_address' => null,
                    'user_agent' => null,
                ];
            }

            /** @var ServerRequestInterface $request */
            $request = app(ServerRequestInterface::class);
        } catch (\Throwable) {
            return [
                'ip_address' => null,
                'user_agent' => null,
            ];
        }

        return [
            'ip_address' => self::stringValue($request->getServerParams()['REMOTE_ADDR'] ?? null),
            'user_agent' => self::stringValue($request->getHeaderLine('User-Agent')),
        ];
    }

    private static function stringValue(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    private static function encodeDetails(mixed $value): ?string
    {
        if ($value === null || $value === [] || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return trim($value) !== '' ? trim($value) : null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
