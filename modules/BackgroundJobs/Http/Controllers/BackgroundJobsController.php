<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs\Http\Controllers;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\BackgroundJobs\Support\BackgroundJobRepository;
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

        return $this->view('@background_jobs/show', [
            'job' => $job,
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
            event(new ActivityRecordingRequested(
                'background_jobs.run_now',
                (bool) ($result['ok'] ?? false) ? 'Triggered background job.' : 'Failed to trigger background job.',
                'schedule_job',
                null,
                [
                    'registry_id' => $registryId,
                    'result' => $result,
                ]
            ));
        }

        session()->flash('background_jobs.notice', (string) ($result['message'] ?? 'Task execution finished.'));

        return $this->redirect('/admin/background-jobs/' . rawurlencode($this->encodeRegistryId($registryId)));
    }

    private function decodeRegistryId(string $routeId): string
    {
        return rawurldecode($routeId);
    }

    private function encodeRegistryId(string $registryId): string
    {
        return rawurlencode($registryId);
    }
}
