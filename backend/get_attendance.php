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
    $sql = 'SELECT a.*, u.name, u.email, d.department_name
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE 1=1 ';

    // role filtering: assume role_id mapping: 1=HR, 2=HOC, 3=Employee
    // HR: role_id == 1 -> no additional where
    if ($roleId == 2) { // HOC
        $sql .= ' AND u.department_id = ?';
        $params[] = $departmentId;
    } elseif ($roleId == 3) { // Employee
        $sql .= ' AND a.user_id = ?';
        $params[] = $userId;
    }

    if ($from) {
        $sql .= ' AND a.attendance_date >= ?';
        $params[] = $from;
    }
    if ($to) {
        $sql .= ' AND a.attendance_date <= ?';
        $params[] = $to;
    }

    $sql .= ' ORDER BY a.attendance_date DESC, a.check_in DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
