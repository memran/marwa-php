<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\DashboardStatus\DashboardStatusCards;
use App\Modules\Dashboard\Support\WidgetRegistry;
use Marwa\Framework\Authorization\AuthManager as FrameworkAuthManager;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;

final class DashboardController extends Controller
{
    private const TABLE = 'dashboard_widgets';

    public function __construct(
        private readonly WidgetRegistry $widgetRegistry,
    ) {}

    public function index(): ResponseInterface
    {
        $userId = $this->getUserId();
        $widgets = $this->getUserWidgets($userId);

        return $this->view('@dashboard/index', [
            'status_cards' => $this->statusCards(),
            'activities' => $this->recentActivities(),
            'widgets' => $widgets,
            'available_widgets' => $this->widgetRegistry->all(),
            'size_options' => $this->widgetRegistry->getSizeOptions(),
            'is_edit_mode' => false,
        ]);
    }

    public function widgets(): ResponseInterface
    {
        $userId = $this->getUserId();
        $widgets = $this->getUserWidgets($userId);

        return $this->json([
            'widgets' => $widgets,
            'available_widgets' => $this->widgetRegistry->all(),
        ]);
    }

    public function saveWidgets(): ResponseInterface
    {
        $userId = $this->getUserId();
        $widgets = request('widgets', []);

        if (!is_array($widgets)) {
            return $this->json(['success' => false, 'message' => 'Invalid data']);
        }

        $this->saveUserWidgets($userId, $widgets);

        return $this->json(['success' => true, 'message' => 'Dashboard saved']);
    }

    public function reset(): ResponseInterface
    {
        $userId = $this->getUserId();

        if ($userId !== null) {
            $pdo = db()->getPdo();
            $stmt = $pdo->prepare("DELETE FROM " . self::TABLE . " WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
        }

        return $this->json(['success' => true, 'message' => 'Dashboard reset to default']);
    }

    public function widgetContent(string $id): ResponseInterface
    {
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

    public function refreshWidget(string $id): ResponseInterface
    {
        $widget = $this->widgetRegistry->get($id);

        if (!$widget) {
            return $this->json(['success' => false, 'message' => 'Widget not found']);
        }

        $content = $this->renderWidget($id);

        return $this->json([
            'success' => true,
            'id' => $id,
            'content' => $content,
        ]);
    }

    private function getUserId(): ?int
    {
        if (!app()->has(FrameworkAuthManager::class)) {
            return null;
        }

        /** @var FrameworkAuthManager $auth */
        $auth = app(FrameworkAuthManager::class);

        return $auth->id();
    }

    /**
     * @return list<array<string, mixed>>
     */
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

        $widgets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($widgets)) {
            return $this->getDefaultWidgets();
        }

        return $widgets;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getDefaultWidgets(): array
    {
        $pdo = db()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT * FROM " . self::TABLE . " WHERE user_id IS NULL OR user_id = '' ORDER BY position ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param list<array<string, mixed>> $widgets
     */
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
        $viewFile = dirname(__DIR__, 3) . '/modules/Dashboard/resources/views/widgets/' . $id . '.twig';

        if (!file_exists($viewFile)) {
            return '<div class="p-4 text-slate-400 dark:text-slate-500">Widget template not found</div>';
        }

        try {
            $view = app()->make(View::class);

            $data = [];
            
            if ($id !== 'theme_info' && class_exists(\App\Modules\DashboardStatus\DashboardStatusCards::class)) {
                $statusCards = app(\App\Modules\DashboardStatus\DashboardStatusCards::class);
                $cards = $statusCards->cards();
                
                $cardMap = [
                    'app_status' => 0,
                    'runtime_info' => 1,
                    'memory_usage' => 2,
                    'disk_space' => 3,
                    'load_average' => 4,
                    'theme_info' => 5,
                ];
                
                $data['card'] = $cards[$cardMap[$id] ?? 0] ?? null;
            }

            return $view->render('@dashboard/widgets/' . $id, $data);
        } catch (\Throwable $e) {
            return '<div class="p-4 text-slate-400 dark:text-slate-500">Error: ' . $e->getMessage() . '</div>';
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function statusCards(): array
    {
        if (!class_exists(DashboardStatusCards::class)) {
            return [];
        }

        return app(DashboardStatusCards::class)->cards();
    }

    /**
     * @return list<\App\Modules\Activity\Models\Activity>
     */
    private function recentActivities(): array
    {
        if (!class_exists(ActivityRecorder::class)) {
            return [];
        }

        return app(ActivityRecorder::class)->recent(5);
    }
}
