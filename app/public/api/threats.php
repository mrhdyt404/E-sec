<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../bootstrap.php';
header('Content-Type: application/json');


$pdo = Database::connect();

$list = $pdo->query("SELECT ip, state, score, reason, last_seen FROM threats ORDER BY last_seen DESC limit 10")
            ->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($list);
