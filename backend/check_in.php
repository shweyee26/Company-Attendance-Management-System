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
$employeeCode = $_SESSION['employee_code'] ?? '';
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$currentTime = date('H:i:s');

if ($employeeCode === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Employee code is missing from your session. Please log in again.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE employee_code = ? AND attendance_date = ?');
    $stmt->execute([$employeeCode, $today]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if ($row['check_in']) {
            echo json_encode(['error' => 'You have already checked in for today.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE attendance SET check_in = ? WHERE id = ?');
        $stmt->execute([$now, $row['id']]);
    } else {
        $columnsStmt = $pdo->query('SHOW COLUMNS FROM attendance');
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (in_array('user_id', $columns, true)) {
            $stmt = $pdo->prepare('INSERT INTO attendance (user_id, employee_code, attendance_date, check_in) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $employeeCode, $today, $now]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO attendance (employee_code, attendance_date, check_in) VALUES (?, ?, ?)');
            $stmt->execute([$employeeCode, $today, $now]);
        }
    }

    echo json_encode(['success' => true, 'check_in' => $currentTime]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
