<?php

declare(strict_types=1);

namespace App\Modules\Activity\Support;

use App\Modules\Activity\Models\Activity;

final class ActivityTimeline
{
    /**
     * @param array<string, scalar|list<string>|null> $params
     * @return array{
     *     data:list<Activity>,
     *     total:int,
     *     pagination:array{summary:string,links:list<array{page:string,url:string,active:bool}>}
     * }
     */
    public function actorEmail(string $email, string $path, int $page = 1, int $perPage = 5, array $params = []): array
    {
        $pageData = $this->pageData($email, $page, $perPage);

        return [
            'data' => $pageData['data'],
            'total' => $pageData['pagination']['total'],
            'pagination' => $this->pagination($pageData['pagination'], $path, $params),
        ];
    }

    /**
     * @return array{data:list<Activity>,pagination:array{total:int,per_page:int,current_page:int,last_page:int}}
     */
    private function pageData(string $email, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        try {
            $activity = new Activity();
            $query = Activity::query();
            $builder = $query->getBaseBuilder();

            $activity->scopeActorEmail($builder, $email);
            $activity->scopeSort($builder, 'created_at', 'desc');

            $pageData = $query->paginate($perPage, $page);
        } catch (\Throwable) {
            $pageData = [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1,
            ];
        }

        return [
            'data' => array_values(array_filter(
                $pageData['data'],
                static fn (mixed $row): bool => $row instanceof Activity
            )),
            'pagination' => [
                'total' => (int) $pageData['total'],
                'per_page' => (int) $pageData['per_page'],
                'current_page' => (int) $pageData['current_page'],
                'last_page' => (int) $pageData['last_page'],
            ],
        ];
    }

    /**
     * @param array{total:int,per_page:int,current_page:int,last_page:int} $pageData
     * @param array<string, scalar|list<string>|null> $params
     * @return array{summary:string,links:list<array{page:string,url:string,active:bool}>}
     */
    private function pagination(array $pageData, string $path, array $params): array
    {
        $pagination = pagination_view_data($pageData, $path, $params, 'activity_page');

        return [
            'summary' => $pagination['summary'],
            'links' => array_map(
                static fn (array $link): array => [
                    'page' => (string) $link['page'],
                    'url' => $link['url'],
                    'active' => $link['active'],
                ],
                $pagination['links']
            ),
        ];
    }
}
