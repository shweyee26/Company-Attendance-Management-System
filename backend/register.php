<?php
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';
$employee_code = trim($data['employee_code'] ?? '');
$department_id = intval($data['department_id'] ?? 0);
$role_id = intval($data['role_id'] ?? 0);

if (!$name || !$email || !$password || !$confirm_password || !$employee_code || !$role_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // ensure unique email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // ensure unique employee code because attendance links to users by employee_code
    $stmt = $pdo->prepare('SELECT id FROM users WHERE employee_code = ?');
    $stmt->execute([$employee_code]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Employee code already registered']);
        exit;
    }

    if ($department_id) {
        $departmentStmt = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
        $departmentStmt->execute([$department_id]);
        if (!$departmentStmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid department selected']);
            exit;
        }
    }

    // validate selected role exists in the database
    $roleTable = 'user_roles';
    $tableCheck = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    $tableCheck->execute([$config['db_name'], $roleTable]);
    if (!$tableCheck->fetchColumn()) {
        $roleTable = 'user_role';
        $tableCheck->execute([$config['db_name'], $roleTable]);
        if (!$tableCheck->fetchColumn()) {
            http_response_code(500);
            echo json_encode(['error' => 'Role table not found']);
            exit;
        }
    }

    $roleStmt = $pdo->prepare("SELECT id FROM {$roleTable} WHERE id = ?");
    $roleStmt->execute([$role_id]);
    $role = $roleStmt->fetchColumn();

    if (!$role) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role selected']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (name, employee_code, email, password_hash, department_id, role_id) VALUES (?,?,?,?,?,?)');
    $insert->execute([$name, $employee_code, $email, $password_hash, $department_id ?: null, $role]);

    echo json_encode(['success' => true, 'user_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
