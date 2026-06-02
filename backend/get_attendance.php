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
$sessionEmployeeCode = $_SESSION['employee_code'] ?? '';

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$departmentFilterId = intval($_GET['department_id'] ?? 0);
$departmentName = trim($_GET['department'] ?? '');
$employeeCode = trim($_GET['employee_code'] ?? '');

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $params = [];
    $sql = "SELECT a.id,
                   u.id AS user_id,
                   a.attendance_date,
                   DATE_FORMAT(a.check_in, '%H:%i:%s') AS check_in,
                   DATE_FORMAT(a.check_out, '%H:%i:%s') AS check_out,
                   u.name,
                   a.employee_code,
                   u.email,
                   d.department_name
            FROM attendance a
            JOIN users u ON u.employee_code = a.employee_code
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE 1=1 ";

    // role filtering: assume role_id mapping: 1=HR, 2=HOD/HOC, 3=Employee
    // HR: role_id == 1 -> no additional where
    if ($roleId == 2) { // HOD/HOC
        $sql .= ' AND u.department_id = ?';
        $params[] = $departmentId;
    } elseif ($roleId == 3) { // Employee
        $sql .= ' AND a.employee_code = ?';
        $params[] = $sessionEmployeeCode;
    }

    if ($from) {
        $sql .= ' AND a.attendance_date >= ?';
        $params[] = $from;
    }
    if ($to) {
        $sql .= ' AND a.attendance_date <= ?';
        $params[] = $to;
    }
    if ($roleId == 1 && $departmentFilterId > 0) {
        $sql .= ' AND u.department_id = ?';
        $params[] = $departmentFilterId;
    } elseif ($roleId == 1 && $departmentName !== '') {
        $sql .= ' AND d.department_name LIKE ?';
        $params[] = '%' . $departmentName . '%';
    }
    if (($roleId == 1 || $roleId == 2) && $employeeCode !== '') {
        $sql .= ' AND a.employee_code LIKE ?';
        $params[] = '%' . $employeeCode . '%';
    }

    $sql .= ' ORDER BY a.attendance_date DESC, a.check_in DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['role_id' => (int)$roleId, 'data' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
