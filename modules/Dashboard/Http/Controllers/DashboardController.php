<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Dashboard\Support\DashboardWidgetRepository;
use App\Modules\Dashboard\Support\WidgetRegistry;
use App\Support\PermissionGate;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly WidgetRegistry $widgetRegistry,
        private readonly DashboardWidgetRepository $widgets,
        private readonly AuthManager $auth,
        private readonly PermissionGate $gate,
    ) {}

    public function index(): ResponseInterface
    {
        if ($this->gate->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();
        $widgets = array_map(
            fn (array $widget): array => $this->hydrateWidget($widget),
            $this->getUserWidgets($userId)
        );

        return $this->view('@dashboard/index', [
            'widgets' => $widgets,
            'available_widgets' => $this->filteredWidgets(),
            'size_options' => $this->widgetRegistry->getSizeOptions(),
            'is_edit_mode' => false,
        ]);
    }

    public function widgets(): ResponseInterface
    {
        if ($this->gate->denies('dashboard.view')) {
            return $this->forbidden();
        }

        return $this->json([
            'widgets' => $this->getUserWidgets($this->getUserId()),
            'available_widgets' => $this->filteredWidgets(),
        ]);
    }

    private function filteredWidgets(): array
    {
        return $this->widgetRegistry->filterByPermission(
            fn (string $permission): bool => $this->gate->allows($permission)
        );
    }

    public function saveWidgets(): ResponseInterface
    {
        if ($this->gate->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();
        $widgets = $this->extractWidgetsPayload($this->request());

        if (!is_array($widgets)) {
            return $this->json(['success' => false, 'message' => 'Invalid data']);
        }

        $widgets = $this->normalizeWidgetsPayload($widgets);
        $this->widgets->saveForUser(
            $userId,
            $widgets,
            fn (string $widgetId): ?array => $this->widgetRegistry->get($widgetId)
        );

        event(new ActivityRecordingRequested(
            'dashboard.saved',
            'Saved dashboard widgets.',
            'dashboard',
            null,
            ['state' => ['widgets' => array_map(static fn (array $widget): string => (string) ($widget['widget_id'] ?? ''), $widgets)]]
        ));

        return $this->json(['success' => true, 'message' => 'Dashboard saved']);
    }

    public function reset(): ResponseInterface
    {
        if ($this->gate->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();

        if ($userId !== null) {
            $this->widgets->resetForUser($userId);
        }

        event(new ActivityRecordingRequested(
            'dashboard.reset',
            'Reset dashboard widgets.',
            'dashboard',
            null,
            ['state' => ['reset' => true]]
        ));
        return $this->json(['success' => true, 'message' => 'Dashboard reset to default']);
    }

    public function widgetContent(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        if ($this->gate->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $id = (string) ($vars['id'] ?? '');
        $widget = $this->widgetRegistry->get($id);

        if (!$widget) {
            return $this->json(['error' => 'Widget not found'], 404);
        }

        $content = $this->renderWidget($id);

        return $this->json([
            'id' => $id,
            'content' => $content,
        ]);
    }

    public function refreshWidget(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        if ($this->gate->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $id = (string) ($vars['id'] ?? '');
        $widget = $this->widgetRegistry->get($id);

        if (!$widget) {
            return $this->json(['success' => false, 'message' => 'Widget not found']);
        }

        return $this->json([
            'success' => true,
            'id' => $id,
            'card' => $this->widgetCard($id),
        ]);
    }

    private function getUserId(): ?int
    {
        return $this->auth->user()?->getId();
    }

    private function getUserWidgets(?int $userId): array
    {
        return $this->widgets->forUser($userId);
    }

    private function renderWidget(string $id): string
    {
        $widget = $this->widgetRegistry->get($id);

        if (!$widget) {
            return '<div class="p-4 text-slate-400 dark:text-slate-500">Widget template not found</div>';
        }

        try {
            $view = app()->view();
            $namespace = (string) ($widget['namespace'] ?? 'dashboard');
            $viewName = (string) ($widget['view'] ?? ('widgets/' . $id));
            $card = $this->widgetCard($id) ?? ($widget['card'] ?? []);

            return $view->render('@' . $namespace . '/' . $viewName, [
                'card' => is_array($card) ? $card : [],
                'widget' => $widget,
            ]);
        } catch (\Throwable) {
            return '<div class="p-4 text-slate-400 dark:text-slate-500">Widget could not be rendered.</div>';
        }
    }

    /**
     * @param array<string, mixed> $widget
     * @return array<string, mixed>
     */
    private function hydrateWidget(array $widget): array
    {
        $widget['content'] = $this->renderWidget((string) ($widget['widget_id'] ?? ''));

        return $widget;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function widgetCard(string $id): ?array
    {
        $widget = $this->widgetRegistry->get($id);
        $widgetCard = $widget['card'] ?? null;

        if (is_array($widgetCard) && $widgetCard !== []) {
            return $widgetCard;
        }

        if (!class_exists(\App\Modules\DashboardStatus\DashboardStatusCards::class)) {
            return null;
        }

        return app(\App\Modules\DashboardStatus\DashboardStatusCards::class)->card($id);
    }

    /**
     * @param ServerRequestInterface $request
     * @return array<int, array<string, mixed>>|mixed
     */
    private function extractWidgetsPayload(ServerRequestInterface $request): mixed
    {
        $parsed = $request->getParsedBody();

        if (is_array($parsed) && array_key_exists('widgets', $parsed)) {
            return $parsed['widgets'];
        }

        $body = (string) $request->getBody();
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded) || !array_key_exists('widgets', $decoded)) {
            return [];
        }

        return $decoded['widgets'];
    }

    /**
     * @param array<int, mixed> $widgets
     * @return list<array<string, mixed>>
     */
    private function normalizeWidgetsPayload(array $widgets): array
    {
        $normalized = [];
        $allowedWidths = array_keys($this->widgetRegistry->getSizeOptions());

        foreach ($widgets as $widget) {
            if (!is_array($widget)) {
                continue;
            }

            $widgetId = trim((string) ($widget['widget_id'] ?? ''));
            if ($widgetId === '' || $this->widgetRegistry->get($widgetId) === null) {
                continue;
            }

            $width = (string) ($widget['width'] ?? 'medium');

            $normalized[] = [
                'widget_id' => $widgetId,
                'widget_type' => (string) ($widget['widget_type'] ?? 'system'),
                'title' => trim((string) ($widget['title'] ?? '')),
                'width' => in_array($width, $allowedWidths, true) ? $width : 'medium',
                'enabled' => (bool) ($widget['enabled'] ?? true),
                'config' => is_array($widget['config'] ?? null) ? $widget['config'] : [],
            ];
        }

        return $normalized;
    }
}
