<?php

declare(strict_types=1);

namespace App\Modules\Activity\Http\Controllers;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Support\AdminListState;
use App\Support\Pagination;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class ActivityController extends Controller
{
    public function index(): ResponseInterface
    {
        /** @var ActivityRecorder $recorder */
        $recorder = app(ActivityRecorder::class);
        /** @var AdminListState $listState */
        $listState = app(AdminListState::class);
        /** @var Pagination $pagination */
        $pagination = app(Pagination::class);
        $state = $listState->state();
        $activities = $recorder->paginated(
            $state['query'],
            $state['page'],
            null,
            $state['filter'],
            $state['sort'],
            $state['direction']
        );

        return $this->view('@user_activity/index', [
            'activities' => $activities,
            'query' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'pagination' => $pagination->viewData($activities, '/admin/activity', [
                'q' => $state['query'],
                'filter' => $state['filter'],
                'sort' => $state['sort'],
                'direction' => $state['direction'],
            ]),
        ]);
    }
}
