<?php
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true]);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $today = (new DateTime())->format('Y-m-d');
    $now = (new DateTime())->format('Y-m-d H:i:s');
    $upd = $pdo->prepare('UPDATE attendance SET check_out = ? WHERE user_id = ? AND attendance_date = ?');
    $upd->execute([$now, $_SESSION['user_id'], $today]);

    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
