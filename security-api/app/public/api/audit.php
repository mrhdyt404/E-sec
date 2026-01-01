<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gunakan require_once agar Database.php tidak dideklarasikan ulang
require_once '../bootstrap.php';

try {
    $pdo = Database::connect();

    $stmt = $pdo->query("SELECT ts, ip, action, reason, `user` FROM audit_logs ORDER BY ts DESC LIMIT 100");
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($list);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
