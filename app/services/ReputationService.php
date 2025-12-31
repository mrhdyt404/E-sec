<?php
class ReputationService {
    public static function update(PDO $db, array $data): int {
        $ip     = (string)($data['ip'] ?? '');
        $status = (int)($data['status'] ?? 0);

        if (!$ip) return 0;

        $delta = $status >= 400 ? -10 : +1;

        $stmt = $db->prepare("SELECT score FROM ip_reputation WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newScore = max(-100, min(100, $row['score'] + $delta));
            $db->prepare("UPDATE ip_reputation SET score=? WHERE ip=?")
               ->execute([$newScore, $ip]);
        } else {
            $newScore = max(-100, min(100, 50 + $delta));
            $db->prepare("INSERT INTO ip_reputation (ip, score) VALUES (?,?)")
               ->execute([$ip, $newScore]);
        }

        return $newScore;
    }
}


