<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs\Http\Controllers;

use App\Modules\BackgroundJobs\Support\BackgroundJobRepository;
use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BackgroundJobsController extends Controller
{
    public function __construct(
        private readonly BackgroundJobRepository $repository,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->view('@background_jobs/index', [
            'overview' => $this->repository->overview(),
        ]);
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $registryId = $this->decodeRegistryId(trim((string) ($vars['id'] ?? '')));
        $job = $registryId !== '' ? $this->repository->find($registryId) : null;

        if ($job === null) {
            return $this->redirect('/admin/background-jobs');
        }

        $notice = session('background_jobs.notice');

        return $this->view('@background_jobs/show', [
            'job' => $job,
            'notice' => is_string($notice) ? $notice : null,
        ]);
    }

    public function runNow(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $registryId = $this->decodeRegistryId(trim((string) ($vars['id'] ?? '')));
        if ($registryId === '') {
            return $this->redirect('/admin/background-jobs');
        }

        $result = $this->repository->runNow($registryId);
        $job = $this->repository->find($registryId);

        if ($job !== null) {
            app(ActivityRecorder::class)->recordActorAction(
                'background_jobs.run_now',
                (bool) ($result['ok'] ?? false) ? 'Triggered background job.' : 'Failed to trigger background job.',
                app(AuthManager::class)->user(),
                'schedule_job',
                null,
                [
                    'registry_id' => $registryId,
                    'result' => $result,
                ]
            );
        }

        session()->flash('background_jobs.notice', (string) ($result['message'] ?? 'Task execution finished.'));

        return $this->redirect('/admin/background-jobs/' . rawurlencode($this->encodeRegistryId($registryId)));
    }

    private function decodeRegistryId(string $routeId): string
    {
        return str_replace('__', '.', $routeId);
    }

    private function encodeRegistryId(string $registryId): string
    {
        return str_replace('.', '__', $registryId);
    }
}
