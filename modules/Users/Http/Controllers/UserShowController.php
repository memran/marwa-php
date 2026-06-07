<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Support\ActivityTimeline;
use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserShowController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ActivityTimeline $activities,
    ) {}

    /**
     * @param array<string, mixed> $vars
     */
    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activity = $this->activities->actorEmail(
            (string) $user->getAttribute('email'),
            '/admin/users/' . $user->getKey(),
            $activityPage,
            5,
            ['tab' => 'activity']
        );

        return $this->view('@users/show', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activity['data'],
            'activity_total' => $activity['total'],
            'activity_pagination' => $activity['pagination'],
        ]);
    }
}
