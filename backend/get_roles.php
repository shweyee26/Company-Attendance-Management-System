<?php
$config = require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $table = 'user_roles';
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    $check->execute([$config['db_name'], $table]);
    if (!$check->fetchColumn()) {
        $table = 'user_role';
        $check->execute([$config['db_name'], $table]);
        if (!$check->fetchColumn()) {
            throw new Exception('Role table not found');
        }
    }

    $stmt = $pdo->prepare("SELECT id, role_name FROM {$table} ORDER BY id");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['roles' => $roles]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load roles']);
}
