<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// cukup require_once supaya tidak redeclare
require '../bootstrap.php';

$ip = $_GET['ip'] ?? null;

if (!$ip) {
    echo json_encode(['error'=>'IP tidak diberikan']);
    exit;
}

$requests = $pdo->prepare("SELECT ts, ip, method, path, status, response_time_ms, bytes, user_agent, server, country, city  FROM traffic_logs WHERE ip=:ip ORDER BY ts DESC LIMIT 50");
$requests->execute(['ip'=>$ip]);

echo json_encode($requests->fetchAll(PDO::FETCH_ASSOC));
