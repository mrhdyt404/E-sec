<?php
class AnomalyService {

    public static function check(PDO $pdo, array $d): void {

        $ml = [
            'ml_pred' => 1,
            'ml_score' => 0,
            'final_score' => 0,
            'rule_score' => 0
        ];

        // ================= ML SCORING =================
        $cmd = "echo " . escapeshellarg(json_encode($d)) . " | python3 /opt/ml/ml_score.py 2>/dev/null";
        $json = shell_exec($cmd);

        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $ml = array_merge($ml, $decoded);
            }
        }

        $finalScore = floatval($ml['final_score']); // dari ML + rules
        $mlScore    = floatval($ml['ml_score']);
        $mlPred     = intval($ml['ml_pred']);
        $ruleScore  = floatval($ml['rule_score']);

        // ================= FLAGGING =================
        $flagged = (
            $mlPred == -1 ||
            $finalScore >= 1 ||
            ($d['status'] ?? 0) >= 500
        ) ? 1 : 0;

        // ================= TYPE =================
        $type = match (true) {
            $mlPred === -1 => 'ml_anomaly',
            ($d['status'] ?? 0) >= 400 => 'http_error',
            default => 'normal'
        };

        // ================= ABUSE CHECK =================
        $abuse = $pdo->prepare("
            SELECT COUNT(*) FROM anomalies
            WHERE ip = ?
              AND ts > NOW() - INTERVAL 1 MINUTE
              AND type = 'http_error'
        ");
        $abuse->execute([$d['ip']]);
        if ($abuse->fetchColumn() > 5) {
            $flagged = 1;
        }

        // ================= HUMAN READABLE REASON =================
        $reason = sprintf(
            "ML:%s | Score:%.2f | Rules:%.2f | Status:%s | Latency:%sms | Bytes:%s",
            $mlPred === -1 ? 'Anomaly' : 'Normal',
            $mlScore,
            $ruleScore,
            $d['status'] ?? '-',
            $d['response_time_ms'] ?? '-',
            $d['bytes'] ?? '-'
        );

        // ================= STORE ANOMALY =================
        $stmt = $pdo->prepare("
            INSERT INTO anomalies
            (ts, ip, path, type, score, description, anomaly_score, flagged)
            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $d['ip'] ?? 'unknown',
            $d['path'] ?? '-',
            $type,
            $finalScore,
            $reason,
            $mlScore,
            $flagged
        ]);

        // ================= INSERT / UPDATE THREATS =================
        if ($flagged) {

            $state = match($type) {
                'ml_anomaly', 'http_error' => 'anomaly',
                default => 'suspicious'
            };

            $stmtThreat = $pdo->prepare("
                INSERT INTO threats
                (ip, state, score, reason, last_seen, created_at)
                VALUES (:ip, :state, :score, :reason, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    state = IF(state='anomaly', state, VALUES(state)),
                    score = VALUES(score),
                    reason = VALUES(reason),
                    last_seen = VALUES(last_seen)
            ");

            $stmtThreat->execute([
                ':ip'     => $d['ip'] ?? 'unknown',
                ':state'  => $state,
                ':score'  => $finalScore,
                ':reason' => $reason
            ]);
        }

        // ================= ALERT =================
        if ($flagged && class_exists('AlertService')) {
            AlertService::sendFCM(
                "🚨 Anomaly Detected",
                "{$d['ip']} {$type} score={$finalScore}"
            );
        }

        // ================= AUTO BLOCK =================
        if ($flagged && $finalScore >= 1.5 && class_exists('AutoBlockService')) {
            AutoBlockService::block(
                $pdo,
                $d['ip'],
                $finalScore,
                "Auto block: score {$finalScore}",
                $type
            );
        }

        // ================= DEBUG LOG =================
        file_put_contents('/tmp/anomaly_debug.log', json_encode([
            'ip' => $d['ip'] ?? '-',
            'type' => $type,
            'flagged' => $flagged,
            'score' => $finalScore,
            'reason' => $reason
        ]) . PHP_EOL, FILE_APPEND);
    }
}
