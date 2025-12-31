<?php

class AnomalyService {

    public static function check(PDO $pdo, array $d): void {

        $ml = [
            'ml_pred' => 1,
            'ml_score' => 0,
            'final_score' => 0,
            'rule_score' => 0
        ];

        $cmd = "echo " . escapeshellarg(json_encode($d)) . " | python3 /opt/ml/ml_score.py 2>/dev/null";
        $json = shell_exec($cmd);

        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $ml = array_merge($ml, $decoded);
            }
        }

        $finalScore = floatval($ml['final_score']);
        $mlScore    = floatval($ml['ml_score']);
        $mlPred     = intval($ml['ml_pred']);

        // ================= RULE BASE SCORE =================
        $ruleScore = 0;
        if (($d['status'] ?? 0) == 401)  $ruleScore += 0.5;
        if (($d['status'] ?? 0) >= 500)  $ruleScore += 0.7;
        if (($d['response_time_ms'] ?? 0) > 1500) $ruleScore += 0.5;

        $finalScore += $ruleScore;

        // ================= FLAGGING =================
        $flagged = (
            $mlPred == -1 ||
            $finalScore >= 1 ||
            ($d['status'] ?? 0) >= 500
        ) ? 1 : 0;

        // ================= TYPE =================
        $type = $mlPred === -1
            ? 'ml_anomaly'
            : (($d['status'] ?? 0) >= 400 ? 'http_error' : 'normal');

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

        // ================= STORE =================
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
            json_encode($ml, JSON_UNESCAPED_SLASHES),
            $mlScore,
            $flagged
        ]);

        // ================= ALERT =================
        if ($flagged && class_exists('AlertService')) {
            AlertService::sendFCM(
                "🚨 Anomaly Detected",
                "{$d['ip']} score {$finalScore} ({$type})"
            );
        }
        
        if ($flagged && $finalScore >= 1.5) {
            AutoBlockService::block(
                $pdo,
                $d['ip'],
                $finalScore,
                "Anomaly score {$finalScore}",
                $type
            );
        }
    }
}
