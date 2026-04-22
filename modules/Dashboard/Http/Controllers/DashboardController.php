<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Modules\Dashboard\Support\WidgetRegistry;
use App\Modules\Auth\Support\AuthManager;
use App\Support\PermissionGate;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Views\View;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DashboardController extends Controller
{
    private const TABLE = 'dashboard_widgets';

    public function __construct(
        private readonly WidgetRegistry $widgetRegistry,
    ) {}

    public function index(): ResponseInterface
    {
        if ($this->gate()->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();
        $widgets = array_map(
            fn (array $widget): array => $this->hydrateWidget($widget),
            $this->getUserWidgets($userId)
        );

        return $this->view('@dashboard/index', [
            'widgets' => $widgets,
            'available_widgets' => $this->widgetRegistry->all(),
            'size_options' => $this->widgetRegistry->getSizeOptions(),
            'is_edit_mode' => false,
        ]);
    }

    public function widgets(): ResponseInterface
    {
        if ($this->gate()->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();
        $widgets = $this->getUserWidgets($userId);

        return $this->json([
            'widgets' => $widgets,
            'available_widgets' => $this->widgetRegistry->all(),
        ]);
    }

    public function saveWidgets(): ResponseInterface
    {
        if ($this->gate()->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();
        $widgets = $this->extractWidgetsPayload($this->request());

        if (!is_array($widgets)) {
            return $this->json(['success' => false, 'message' => 'Invalid data']);
        }

        $this->saveUserWidgets($userId, $widgets);
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'dashboard.saved',
            'Saved dashboard widgets.',
            app(\App\Modules\Auth\Support\AuthManager::class)->user() instanceof \App\Modules\Users\Models\User ? app(\App\Modules\Auth\Support\AuthManager::class)->user() : null,
            'dashboard',
            null,
            ['state' => ['widgets' => array_map(static fn (array $widget): string => (string) ($widget['widget_id'] ?? ''), $widgets)]]
        );

        return $this->json(['success' => true, 'message' => 'Dashboard saved']);
    }

    public function reset(): ResponseInterface
    {
        if ($this->gate()->denies('dashboard.view')) {
            return $this->forbidden();
        }

        $userId = $this->getUserId();

        if ($userId !== null) {
            $pdo = db()->getPdo();
            $stmt = $pdo->prepare("DELETE FROM " . self::TABLE . " WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
        }

        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'dashboard.reset',
            'Reset dashboard widgets.',
            app(\App\Modules\Auth\Support\AuthManager::class)->user() instanceof \App\Modules\Users\Models\User ? app(\App\Modules\Auth\Support\AuthManager::class)->user() : null,
            'dashboard',
            null,
            ['state' => ['reset' => true]]
        );
        return $this->json(['success' => true, 'message' => 'Dashboard reset to default']);
    }

    public function widgetContent(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        if ($this->gate()->denies('dashboard.view')) {
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
        if ($this->gate()->denies('dashboard.view')) {
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
        return app(AuthManager::class)->user()?->getId();
    }

    private function gate(): PermissionGate
    {
        return app(PermissionGate::class);
    }

    private function getUserWidgets(?int $userId): array
    {
        $pdo = db()->getPdo();

        if ($userId !== null) {
            $stmt = $pdo->prepare(
                "SELECT * FROM " . self::TABLE . " WHERE user_id = :user_id ORDER BY position ASC"
            );
            $stmt->execute(['user_id' => $userId]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT * FROM " . self::TABLE . " WHERE user_id IS NULL ORDER BY position ASC"
            );
            $stmt->execute();
        }

        $widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($widgets)) {
            return $this->getDefaultWidgets();
        }

        return $widgets;
    }

    private function getDefaultWidgets(): array
    {
        $pdo = db()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT * FROM " . self::TABLE . " WHERE user_id IS NULL ORDER BY position ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveUserWidgets(?int $userId, array $widgets): void
    {
        $pdo = db()->getPdo();

        if ($userId !== null) {
            $stmt = $pdo->prepare("DELETE FROM " . self::TABLE . " WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            "INSERT INTO " . self::TABLE . " 
             (user_id, widget_id, widget_type, title, position, width, enabled, config, created_at, updated_at) 
             VALUES (:user_id, :widget_id, :widget_type, :title, :position, :width, :enabled, :config, :created_at, :updated_at)"
        );

        foreach ($widgets as $index => $widget) {
            $widgetDef = $this->widgetRegistry->get($widget['widget_id'] ?? '');

            $stmt->execute([
                'user_id' => $userId,
                'widget_id' => $widget['widget_id'] ?? '',
                'widget_type' => $widget['widget_type'] ?? 'system',
                'title' => $widget['title'] ?? ($widgetDef['name'] ?? ''),
                'position' => $index,
                'width' => $widget['width'] ?? 'medium',
                'enabled' => $widget['enabled'] ? 1 : 0,
                'config' => json_encode($widget['config'] ?? []),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function renderWidget(string $id): string
    {
        $widget = $this->widgetRegistry->get($id);

        if (!$widget) {
            return '<div class="p-4 text-slate-400 dark:text-slate-500">Widget template not found</div>';
        }

        try {
            $view = app()->make(View::class);
            $namespace = (string) ($widget['namespace'] ?? 'dashboard');
            $viewName = (string) ($widget['view'] ?? ('widgets/' . $id));
            $card = $this->widgetCard($id) ?? ($widget['card'] ?? []);

            return $view->render('@' . $namespace . '/' . $viewName, [
                'card' => is_array($card) ? $card : [],
                'widget' => $widget,
            ]);
        } catch (\Throwable $e) {
            return '<div class="p-4 text-slate-400 dark:text-slate-500">Error: ' . $e->getMessage() . '</div>';
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
}
