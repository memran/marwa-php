<?php

declare(strict_types=1);

namespace App\Modules\Activity\Http\Controllers;

use App\Modules\Activity\Models\Activity;
use App\Support\AdminListState;
use App\Support\Pagination\PaginationResult;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class ActivityController extends Controller
{
    public function index(): ResponseInterface
    {
        /** @var AdminListState $listState */
        $listState = app(AdminListState::class);
        $state = $listState->state();
        $perPage = per_page(20);

        try {
            $activity = new Activity();
            $query = Activity::query();
            $builder = $query->getBaseBuilder();

            $activity->scopeSearch($builder, $state['query']);
            $activity->scopeFilter($builder, $state['filter']);
            $activity->scopeSort($builder, $state['sort'], $state['direction']);

            $activities = $query->paginate(max(1, $perPage), $state['page']);
        } catch (\Throwable) {
            $activities = [
                'data' => [],
                'total' => 0,
                'per_page' => max(1, $perPage),
                'current_page' => $state['page'],
                'last_page' => 1,
            ];
        }

        return $this->view('@user_activity/index', [
            'activities' => $activities,
            'query' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'pagination' => PaginationResult::fromArray([
                'data' => $activities['data'] ?? [],
                'total' => (int) ($activities['total'] ?? 0),
                'per_page' => (int) ($activities['per_page'] ?? $perPage),
                'current_page' => (int) ($activities['current_page'] ?? $state['page']),
                'last_page' => (int) ($activities['last_page'] ?? 1),
            ], '/admin/activity', [
                'q' => $state['query'],
                'filter' => $state['filter'],
                'sort' => $state['sort'],
                'direction' => $state['direction'],
            ]),
        ]);
    }
}
