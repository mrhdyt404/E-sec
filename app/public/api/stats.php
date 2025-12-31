<?php
declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/../../core/Database.php';

try {
    $pdo = Database::connect();

    $headers = getallheaders();
    // $token = trim(str_ireplace('Bearer', '', $headers['Authorization'] ?? ''));

    // if ($token !== 'MYSECRET123') {
    //     http_response_code(401);
    //     exit(json_encode(["error" => "Unauthorized"]));
    // }


    // 1️⃣ Ringkasan 5 menit terakhir
    $summaryStmt = $pdo->query("
        SELECT
          COUNT(*) AS total,
          SUM(status >= 400) AS errors,
          COUNT(DISTINCT ip) AS unique_ips
        FROM traffic_logs
        WHERE ts > NOW() - INTERVAL 5 MINUTE
    ");
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    // 2️⃣ Breakdown per status (5 menit terakhir)
    $statusStmt = $pdo->query("
        SELECT status, COUNT(*) c
        FROM traffic_logs
        WHERE ts > NOW() - INTERVAL 5 MINUTE
        GROUP BY status
        ORDER BY status
    ");
    $byStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3️⃣ Top IP (5 menit terakhir)
    $ipStmt = $pdo->query("
        SELECT ip, COUNT(*) c
        FROM traffic_logs
        WHERE ts > NOW() - INTERVAL 5 MINUTE
        GROUP BY ip
        ORDER BY c DESC
        LIMIT 10
    ");
    $byIP = $ipStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "window" => "5m",
        "summary" => [
            "total"      => (int)($summary['total'] ?? 0),
            "errors"     => (int)($summary['errors'] ?? 0),
            "unique_ips" => (int)($summary['unique_ips'] ?? 0),
        ],
        "status" => $byStatus,
        "ip"     => $byIP
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "stats_failed",
        "message" => $e->getMessage()
    ]);
}
