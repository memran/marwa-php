<?php

$pdo = new PDO('sqlite:' . dirname(__DIR__) . '/database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query('SELECT * FROM notifications WHERE user_id = 1 AND is_read = 0 LIMIT 1');
var_dump($stmt->fetch(PDO::FETCH_ASSOC));
echo "OK\n";