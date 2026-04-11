<?php

declare(strict_types=1);

namespace App\Modules\Activity\Http\Controllers;

use App\Modules\Activity\Support\ActivityRecorder;
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

        return $this->view('@activity/index', [
            'activities' => $recorder->recent(20),
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
