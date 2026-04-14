<?php

$pdo = new PDO('sqlite:' . dirname(__DIR__) . '/database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "No admin user found\n";
    exit;
}

$adminId = $admin['id'];

$notifications = [
    ['type' => 'info', 'title' => 'New user registration', 'message' => 'John Doe has registered a new account.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
    ['type' => 'success', 'title' => 'Payment received', 'message' => 'Payment of $49.99 received from user #42.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['type' => 'warning', 'title' => 'Disk space low', 'message' => 'Server disk usage is at 85%. Consider cleanup.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
    ['type' => 'error', 'title' => 'Payment failed', 'message' => 'Payment attempt for user #38 failed: card declined.', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ['type' => 'info', 'title' => 'System backup completed', 'message' => 'Daily backup completed successfully.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))],
    ['type' => 'success', 'title' => 'New subscription', 'message' => 'User #45 upgraded to Premium plan.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['type' => 'warning', 'title' => 'API rate limit', 'message' => 'API usage at 90% of monthly limit.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['type' => 'info', 'title' => 'New comment', 'message' => 'New comment on your post "Getting Started".', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
    ['type' => 'success', 'title' => 'Export completed', 'message' => 'User data export finished. Download ready.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
    ['type' => 'warning', 'title' => 'Password expiring', 'message' => 'Your password will expire in 3 days.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
    ['type' => 'info', 'title' => 'Weekly report', 'message' => 'Weekly activity report is now available.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
    ['type' => 'error', 'title' => 'Connection timeout', 'message' => 'Database connection timed out during sync.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
    ['type' => 'success', 'title' => 'Feature deployed', 'message' => 'New dashboard widgets deployed successfully.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))],
    ['type' => 'info', 'title' => 'Maintenance scheduled', 'message' => 'System maintenance scheduled for Sunday 2AM.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-8 days'))],
    ['type' => 'warning', 'title' => 'SSL certificate expiring', 'message' => 'SSL certificate expires in 14 days. Renew soon.', 'is_read' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-10 days'))],
];

$stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, is_read, read_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

foreach ($notifications as $n) {
    $readAt = $n['is_read'] ? date('Y-m-d H:i:s') : null;
    $stmt->execute([$adminId, $n['type'], $n['title'], $n['message'], $n['is_read'], $readAt, $n['created_at'], $n['created_at']]);
}

echo "Inserted " . count($notifications) . " fake notifications\n";