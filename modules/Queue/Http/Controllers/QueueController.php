<?php

declare(strict_types=1);

namespace App\Modules\Queue\Http\Controllers;

use App\Modules\Queue\Support\QueueRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class QueueController extends Controller
{
    public function __construct(private readonly QueueRepository $repository) {}

    public function index(): ResponseInterface
    {
        return $this->view('@queue/index', [
            'overview' => $this->repository->overview(),
        ]);
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $jobId = trim((string) ($vars['id'] ?? ''));
        $job = $jobId !== '' ? $this->repository->find($jobId) : null;

        if ($job === null) {
            return $this->redirect('/admin/queue');
        }

        return $this->view('@queue/show', [
            'job' => $job,
        ]);
    }

    public function retry(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $jobId = trim((string) ($vars['id'] ?? ''));

        if ($jobId === '') {
            return $this->redirect('/admin/queue');
        }

        $ok = $this->repository->retry($jobId);
        session()->flash('queue.notice', $ok ? 'Job queued for retry.' : 'Unable to retry job.');

        return $this->redirect('/admin/queue/' . rawurlencode($jobId));
    }
}
