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
$employee_code = trim($data['employee_code'] ?? '');
$department_id = intval($data['department_id'] ?? 0);
$role_id = intval($data['role_id'] ?? 0);

if (!$name || !$email || !$password || !$employee_code || !$role_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
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

    // validate selected role exists in the database
    $roleStmt = $pdo->prepare('SELECT id FROM user_roles WHERE id = ?');
    $roleStmt->execute([$role_id]);
    $role = $roleStmt->fetchColumn();
    if (!$role) {
        $roleStmt = $pdo->prepare('SELECT id FROM user_role WHERE id = ?');
        $roleStmt->execute([$role_id]);
        $role = $roleStmt->fetchColumn();
    }

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
