<?php
$config = require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->prepare('SELECT id, department_name FROM departments ORDER BY department_name');
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['departments' => $departments]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load departments']);
}
