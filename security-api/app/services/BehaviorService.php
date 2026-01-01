<?php

class BehaviorService {

    // ===== Konstanta =====
    const THRESHOLD = 3.0;   // skor >= ini dianggap malicious
    const WINDOW    = 60;    // detik window akumulasi skor
    const FLOOD_THRESHOLD = 5;
    const FLOOD_WINDOW    = 30;

    /**
     * Deteksi scanner / probing dari User-Agent
     */
    public static function isScanner(array $d): bool {
        $ua = strtolower($d['user_agent'] ?? '');

        $badAgents = [
            'scanner','nikto','sqlmap','nmap','masscan',
            'acunetix','dirbuster','wpscan','fuzz','curl'
        ];

        foreach ($badAgents as $bad) {
            if (str_contains($ua, $bad)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi flood error 5xx
     */
    public static function isServerErrorFlood(PDO $pdo, string $ip): bool {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM traffic_logs
            WHERE ip=? AND status >= 500
              AND ts > NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([$ip, self::FLOOD_WINDOW]);

        return (int)$stmt->fetchColumn() >= self::FLOOD_THRESHOLD;
    }

    /**
     * Update skor perilaku per request
     */
    public static function update(PDO $pdo, array $d): float {

        $score = 0.0;

        $status   = intval($d['status'] ?? 0);
        $response = floatval($d['response_time_ms'] ?? 0);

        // Heuristik
        if ($status === 401)  $score += 1.0;
        if ($status >= 500)  $score += 1.0;
        if ($response > 1500) $score += 0.5;

        // Scanner langsung signifikan
        if (self::isScanner($d)) $score += 2.0;

        $stmt = $pdo->prepare("
            INSERT INTO behavior_scores (ts, ip, path, status, score)
            VALUES (NOW(), ?, ?, ?, ?)
        ");
        $stmt->execute([
            $d['ip'],
            $d['path'] ?? '-',
            $status,
            $score
        ]);

        return $score;
    }

    /**
     * Akumulasi skor untuk IP dalam window
     */
    public static function isMalicious(PDO $pdo, string $ip): bool {

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(score), 0)
            FROM behavior_scores
            WHERE ip=? AND ts > NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([$ip, self::WINDOW]);

        $total = floatval($stmt->fetchColumn());

        return $total >= self::THRESHOLD;
    }

}
