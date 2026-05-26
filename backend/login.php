<?php
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing credentials']);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->prepare('SELECT id, employee_code, password_hash, role_id, department_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // set session
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role_id'] = $user['role_id'] ? (int)$user['role_id'] : null;
    $_SESSION['department_id'] = $user['department_id'] ? (int)$user['department_id'] : null;

    // record check-in for today if not exists
    $today = (new DateTime())->format('Y-m-d');
    $checkStmt = $pdo->prepare('SELECT id, check_in FROM attendance WHERE user_id = ? AND attendance_date = ?');
    $checkStmt->execute([$_SESSION['user_id'], $today]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $now = (new DateTime())->format('Y-m-d H:i:s');
    if ($row) {
        if (!$row['check_in']) {
            $upd = $pdo->prepare('UPDATE attendance SET check_in = ? WHERE id = ?');
            $upd->execute([$now, $row['id']]);
        }
    } else {
        $employeeCode = trim((string)($user['employee_code'] ?? ''));
        $ins = $pdo->prepare('INSERT INTO attendance (user_id, employee_code, attendance_date, check_in) VALUES (?,?,?,?)');
        $ins->execute([$_SESSION['user_id'], $employeeCode, $today, $now]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
