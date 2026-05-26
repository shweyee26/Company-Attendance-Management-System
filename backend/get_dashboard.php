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

$roleId = $_SESSION['role_id'];
$departmentId = $_SESSION['department_id'];
$userId = $_SESSION['user_id'];

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $params = [];
    $where = ' WHERE 1=1 ';

    if ($roleId == 2) { // HOC
        $where .= ' AND u.department_id = ?'; $params[] = $departmentId;
    } elseif ($roleId == 3) { // Employee
        $where .= ' AND a.user_id = ?'; $params[] = $userId;
    }
    if ($from) { $where .= ' AND a.attendance_date >= ?'; $params[] = $from; }
    if ($to) { $where .= ' AND a.attendance_date <= ?'; $params[] = $to; }

    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM attendance a JOIN users u ON u.id = a.user_id ' . $where);
    $totalStmt->execute($params);
    $total = $totalStmt->fetchColumn();

    // late definition: check_in > 09:30
    $lateStmt = $pdo->prepare('SELECT COUNT(*) FROM attendance a JOIN users u ON u.id = a.user_id ' . $where . " AND TIME(a.check_in) > '09:00:00'");
    $lateStmt->execute($params);
    $late = $lateStmt->fetchColumn();

    echo json_encode(['total' => (int)$total, 'late' => (int)$late]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
