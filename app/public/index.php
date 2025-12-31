<?php
session_start();

require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../controllers/TrafficController.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

/* =========================
   API ROUTES (NO SESSION)
   ========================= */

if ($uri === '/v1/traffic/collect' && $method === 'POST') {
    header('Content-Type: application/json');
    (new TrafficController())->collect();
    exit;
}

if ($uri === '/internal/broadcast' && $method === 'POST') {
    header('Content-Type: application/json');

    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    $fp = @fsockopen("127.0.0.1", 8080, $errno, $errstr, 0.5);
    if ($fp) {
        fwrite($fp, json_encode($data));
        fclose($fp);
    }

    echo json_encode(["status" => "ok"]);
    exit;
}

/* =========================
   WEB ROUTES
   ========================= */

// /login → login.php
if ($uri === '/login') {
    require __DIR__ . '/login.php';
    exit;
}

// /dashboard → dashboard.php (harus login)
if ($uri === '/dashboard') {
    if (!($_SESSION['auth'] ?? false)) {
        header("Location: /login");
        exit;
    }

    require __DIR__ . '/dashboard.php';
    exit;
}

/* =========================
   DEFAULT ROOT
   ========================= */

if ($uri === '/' || $uri === '') {
    if (!($_SESSION['auth'] ?? false)) {
        header("Location: /login");
    } else {
        header("Location: /dashboard");
    }
    exit;
}

/* =========================
   404 NOT FOUND
   ========================= */

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(["error" => "Not Found"]);
exit;
