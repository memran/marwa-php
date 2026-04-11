<?php

declare(strict_types=1);

namespace App\Modules\Activity\Support;

use App\Modules\Activity\Models\Activity;
use App\Modules\Users\Models\User;
use Marwa\DB\Connection\ConnectionManager;

final class ActivityRecorder
{
    public function record(string $action, string $description, array $context = []): void
    {
        if (!app()->has(ConnectionManager::class)) {
            return;
        }

        $payload = [
            'action' => trim($action),
            'description' => trim($description),
            'actor_name' => $this->stringValue($context['actor_name'] ?? null),
            'actor_email' => $this->stringValue($context['actor_email'] ?? null),
            'subject_type' => $this->stringValue($context['subject_type'] ?? null),
            'subject_id' => $this->integerValue($context['subject_id'] ?? null),
            'details' => $this->encodeDetails($context['details'] ?? null),
        ];

        try {
            $this->persist($payload);
        } catch (\Throwable) {
            return;
        }
    }

    /**
     * @return list<Activity>
     */
    public function recent(int $limit = 10): array
    {
        if (!app()->has(ConnectionManager::class)) {
            return [];
        }

        try {
            $rows = Activity::newQuery()->getBaseBuilder()
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $activities = array_map(
            static fn (array|object $row): Activity => Activity::newInstance(is_array($row) ? $row : (array) $row, true),
            $rows
        );

        return array_slice($activities, 0, max(0, $limit));
    }

    public function recordUserAction(string $action, string $description, ?User $actor, User $subject, array $details = []): void
    {
        $this->record($action, $description, [
            'actor_name' => $actor instanceof User ? $actor->getAttribute('name') : null,
            'actor_email' => $actor instanceof User ? $actor->getAttribute('email') : null,
            'subject_type' => User::class,
            'subject_id' => $subject->getKey(),
            'details' => $details,
        ]);
    }

    public function recordAuthAction(string $action, string $description, ?User $actor = null, array $details = []): void
    {
        $this->record($action, $description, [
            'actor_name' => $actor instanceof User ? $actor->getAttribute('name') : null,
            'actor_email' => $actor instanceof User ? $actor->getAttribute('email') : null,
            'subject_type' => 'auth',
            'subject_id' => null,
            'details' => $details,
        ]);
    }

    private function stringValue(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    private function integerValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function encodeDetails(mixed $value): ?string
    {
        if ($value === null || $value === [] || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return trim($value) !== '' ? trim($value) : null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    private function persist(array $payload): void
    {
        Activity::create($payload);
    }
}
