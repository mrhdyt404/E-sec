<?php

class BlockAuditService {

    public static function log(PDO $pdo, array $data): void {

        $stmt = $pdo->prepare("
            INSERT INTO block_events
            (ts, ip, decision, reason, behavior_score, ml_score, source)
            VALUES (NOW(),?,?,?,?,?,?)
        ");

        $stmt->execute([
            $data['ip'],
            $data['decision'],
            $data['reason'] ?? null,
            $data['behavior_score'] ?? null,
            $data['ml_score'] ?? null,
            $data['source'] ?? 'system'
        ]);
    }

    public static function lastDecision(PDO $pdo, string $ip): ?array {

        $stmt = $pdo->prepare("
            SELECT * FROM block_events 
            WHERE ip = ? ORDER BY ts DESC LIMIT 1
        ");
        $stmt->execute([$ip]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

}
