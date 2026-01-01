<?php
// /api/whitelist.php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
session_start();

// 🔐 Only admin / security
if (empty($_SESSION['user']) || !in_array($_SESSION['role'], ['admin','security'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ip   = trim($data['ip'] ?? '');
$note = trim($data['note'] ?? '');

if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid IP']);
    exit;
}

$pdo = Database::connect();

// 🔎 Already whitelisted?
if (WhitelistService::isWhitelisted($pdo, $ip)) {
    echo json_encode(['status' => 'already_whitelisted']);
    exit;
}

// 🔥 Insert whitelist
$stmt = $pdo->prepare("INSERT INTO whitelisted_ips (ip, note) VALUES (?,?)");
$stmt->execute([$ip, $note]);

// 🧹 If currently blocked → unblock
if (FirewallService::isBlocked($pdo, $ip)) {
    FirewallService::unblock($pdo, $ip, 'whitelist');
}

// 📝 Audit trail
AuditService::log($pdo, $ip, 'whitelist', $note ?: 'manual', $_SESSION['user']);

echo json_encode([
    'status' => 'whitelisted',
    'ip' => $ip
]);
