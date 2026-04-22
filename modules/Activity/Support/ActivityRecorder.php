<?php

declare(strict_types=1);

namespace App\Modules\Activity\Support;

use App\Modules\Activity\Models\Activity;
use App\Support\AdminSearch;
use App\Modules\Users\Models\User;
use Psr\Http\Message\ServerRequestInterface;

final class ActivityRecorder
{
    private readonly AdminSearch $search;

    public function __construct(?AdminSearch $search = null)
    {
        $this->search = $search ?? new AdminSearch();
    }

    public function record(string $action, string $description, array $context = []): void
    {
        $payload = [
            'action' => trim($action),
            'description' => trim($description),
            'actor_name' => $this->stringValue($context['actor_name'] ?? null),
            'actor_email' => $this->stringValue($context['actor_email'] ?? null),
            'ip_address' => $this->stringValue($context['ip_address'] ?? null),
            'user_agent' => $this->stringValue($context['user_agent'] ?? null),
            'subject_type' => $this->stringValue($context['subject_type'] ?? null),
            'subject_id' => $this->integerValue($context['subject_id'] ?? null),
            'details' => $this->encodeDetails($context['details'] ?? null),
        ];

        try {
            Activity::create($payload);
        } catch (\Throwable) {
            return;
        }
    }

    /**
     * @return list<Activity>
     */
    public function recent(string|int $query = '', int $limit = 10): array
    {
        if (is_int($query)) {
            $limit = $query;
            $query = '';
        }

        $limit = max(0, $limit);
        $query = trim($query);

        try {
            $builder = Activity::newQuery()->getBaseBuilder()
                ->orderBy('created_at', 'desc');
            $this->search->applyLikeFilters($builder, $query, [
                'action',
                'description',
                'actor_name',
                'actor_email',
                'ip_address',
                'user_agent',
                'subject_type',
                'details',
            ]);
            $rows = $builder->limit($limit)->get();
        } catch (\Throwable) {
            return [];
        }

        return array_map(
            static fn (array|object $row): Activity => Activity::newInstance(is_array($row) ? $row : (array) $row, true),
            $rows
        );
    }

    /**
     * @return array{data:list<Activity>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginated(string $query = '', int $page = 1, ?int $perPage = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, (int) ($perPage ?? config('settings.lifecycle.pagination.default_per_page', config('pagination.default_per_page', 20))));
        $query = trim($query);

        try {
            $builder = Activity::newQuery()->getBaseBuilder()
                ->orderBy('created_at', 'desc');
            $this->search->applyLikeFilters($builder, $query, [
                'action',
                'description',
                'actor_name',
                'actor_email',
                'ip_address',
                'user_agent',
                'subject_type',
                'details',
            ]);
            $pageData = $builder->paginate($perPage, $page);
        } catch (\Throwable) {
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1,
            ];
        }

        $pageData['data'] = array_map(
            static fn (array|object $row): Activity => Activity::newInstance(is_array($row) ? $row : (array) $row, true),
            $pageData['data']
        );

        return $pageData;
    }

    public function recordActorAction(
        string $action,
        string $description,
        ?User $actor = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $details = []
    ): void
    {
        $this->record($action, $description, [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'details' => $details,
        ] + $this->actorContext($actor) + $this->requestContext());
    }

    /**
     * @return array{actor_name: mixed, actor_email: mixed}
     */
    private function actorContext(?User $actor): array
    {
        return [
            'actor_name' => $actor instanceof User ? $actor->getAttribute('name') : null,
            'actor_email' => $actor instanceof User ? $actor->getAttribute('email') : null,
        ];
    }

    /**
     * @return array{ip_address: mixed, user_agent: mixed}
     */
    private function requestContext(): array
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
            'ip_address' => (string) ($request->getServerParams()['REMOTE_ADDR'] ?? null),
            'user_agent' => $request->getHeaderLine('User-Agent'),
        ];
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
}
