<?php

$pdo = new PDO('sqlite:' . dirname(__DIR__) . '/database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Notifications count: ";
$stmt = $pdo->query('SELECT COUNT(*) as cnt FROM notifications');
echo $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";

echo "\nAdmin users: ";
$stmt = $pdo->query('SELECT id, email, role FROM users WHERE role = "admin" AND is_active = 1');
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nSample notifications: ";
$stmt = $pdo->query('SELECT id, user_id, type, title, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));