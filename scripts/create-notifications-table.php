<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new Marwa\Framework\Application(__DIR__);
$app->boot();

$pdo = app(\Marwa\DB\Connection\ConnectionManager::class)->getPdo();

$sql = 'CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type VARCHAR(20) DEFAULT "info",
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    read_at VARCHAR(50) DEFAULT NULL,
    action_url VARCHAR(255) DEFAULT NULL,
    created_at VARCHAR(50) DEFAULT NULL,
    updated_at VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)';

$pdo->exec($sql);
echo "Created notifications table\n";

// Insert sample notifications for admin
$admin = \App\Modules\Users\Models\User::newQuery()->getBaseBuilder()
    ->where('role', '=', 'admin')
    ->where('is_active', '=', 1)
    ->first();

if ($admin) {
    $adminId = is_array($admin) ? $admin['id'] : $admin->id;
    
    $notifications = [
        ['type' => 'info', 'title' => 'System initialized', 'message' => 'The application has started successfully.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
        ['type' => 'success', 'title' => 'Database connected', 'message' => 'Successfully connected to the database.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
        ['type' => 'info', 'title' => 'Settings loaded', 'message' => 'Application settings have been loaded from the database.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
        ['type' => 'warning', 'title' => 'Cache warming', 'message' => 'Cache is being rebuilt. Some operations may be slower than usual.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
        ['type' => 'info', 'title' => 'Notifications module ready', 'message' => 'The notifications system is now active and ready to receive alerts.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-12 hours'))],
        ['type' => 'success', 'title' => 'Welcome to MarwaPHP', 'message' => 'Your notification bell is now active. Click to see your latest alerts.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ];
    
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, is_read, read_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    
    foreach ($notifications as $n) {
        $readAt = $n['is_read'] ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$adminId, $n['type'], $n['title'], $n['message'], $n['is_read'], $readAt, $n['created_at'], $n['created_at']]);
    }
    
    echo "Inserted sample notifications\n";
}