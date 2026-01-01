<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../bootstrap.php';
$pdo = Database::connect();

$data = json_decode(file_get_contents('php://input'), true);
$ip = $data['ip'] ?? null;

if($ip){
    // Set IP sebagai aktif diblokir
    $stmt = $pdo->prepare("
        INSERT INTO blocked_ips (ip, active, blocked_at) 
        VALUES (:ip, 1, NOW())
        ON DUPLICATE KEY UPDATE active=1, blocked_at=NOW()
    ");
    $stmt->execute(['ip'=>$ip]);

    // Log ke audit
    $pdo->prepare("
        INSERT INTO audit_trail(ip, action, actor) 
        VALUES(:ip,'block','admin')
    ")->execute(['ip'=>$ip]);
}

echo json_encode(['status'=>'ok']);
