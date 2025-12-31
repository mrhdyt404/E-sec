<?php

class BehaviorService {

    const THRESHOLD = 3.0;   // skor >= ini dianggap malicious
    const WINDOW = 60;      // detik

    public static function update(PDO $pdo, array $d): float {
        $score = 0;

        $status = intval($d['status'] ?? 0);
        $response = floatval($d['response_time_ms'] ?? 0);

        // Heuristik perilaku
        if ($status === 401) $score += 1;
        if ($status >= 500) $score += 1;
        if ($response > 1500) $score += 0.5;

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

    public static function isMalicious(PDO $pdo, string $ip): bool {

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(score), 0) as total
            FROM behavior_scores
            WHERE ip = ? AND ts > NOW() - INTERVAL " . self::WINDOW . " SECOND
        ");

        $stmt->execute([$ip]);
        $total = floatval($stmt->fetchColumn());

        return $total >= self::THRESHOLD;
    }

}
