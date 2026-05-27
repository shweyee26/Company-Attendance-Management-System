<?php
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$currentTime = date('H:i:s');

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?');
    $stmt->execute([$userId, $today]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => 'No check-in found for today.']);
        exit;
    }
    if (!$row['check_in']) {
        echo json_encode(['error' => 'You have not checked in yet.']);
        exit;
    }
    if ($row['check_out']) {
        echo json_encode(['error' => 'You already checked out for today.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE attendance SET check_out = ? WHERE id = ?');
    $stmt->execute([$now, $row['id']]);

    echo json_encode(['success' => true, 'check_out' => $currentTime]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
