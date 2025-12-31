<?php

class StatsService {

    public static function requests24h(PDO $pdo): int {
        return (int)$pdo->query("
            SELECT COUNT(*) FROM traffic_logs
            WHERE ts > NOW() - INTERVAL 1 DAY
        ")->fetchColumn();
    }

    public static function avgLatency(PDO $pdo): float {
        return round((float)$pdo->query("
            SELECT AVG(response_time_ms)
            FROM traffic_logs
            WHERE ts > NOW() - INTERVAL 1 DAY
        ")->fetchColumn(), 2);
    }

}
