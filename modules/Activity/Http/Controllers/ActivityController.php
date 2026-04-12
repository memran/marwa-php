<?php

declare(strict_types=1);

namespace App\Modules\Activity\Http\Controllers;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Support\AdminPagination;
use App\Support\AdminSearch;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;

final class ActivityController extends Controller
{
    public function index(): ResponseInterface
    {
        $this->ensureViewNamespace();

        /** @var ActivityRecorder $recorder */
        $recorder = app(ActivityRecorder::class);
        /** @var AdminSearch $search */
        $search = app(AdminSearch::class);
        /** @var AdminPagination $pagination */
        $pagination = app(AdminPagination::class);
        $state = $search->state();
        $activities = $recorder->paginated($state['query'], $state['page']);

        return $this->view('@activity/index', [
            'activities' => $activities,
            'query' => $state['query'],
            'pagination' => $pagination->viewData($activities, '/admin/activity', [
                'q' => $state['query'],
            ]),
        ]);
    }

    private function ensureViewNamespace(): void
    {
        if (!app()->has(View::class)) {
            return;
        }

        app()->view()->addNamespace('activity', dirname(__DIR__, 2) . '/resources/views');
    }
}
