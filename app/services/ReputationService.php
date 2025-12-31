<?php

class ReputationService {

    const STATE_NORMAL     = 'normal';
    const STATE_SUSPICIOUS = 'suspicious';
    const STATE_QUARANTINE = 'quarantine';
    const STATE_MALICIOUS  = 'malicious';

    public static function get(PDO $pdo,$ip){
    $s=$pdo->prepare("SELECT score FROM reputation WHERE ip=?");
    $s->execute([$ip]);
    return $s->fetchColumn() ?: 0;
  }

  public static function markFalsePositive(PDO $pdo,$ip){
    $pdo->prepare("UPDATE reputation SET score=0 WHERE ip=?")->execute([$ip]);
  }

    /**
     * Ambil state reputasi IP
     */
    public static function getState(PDO $pdo, string $ip): string {
        $stmt = $pdo->prepare("SELECT state FROM ip_reputation WHERE ip=?");
        $stmt->execute([$ip]);
        return $stmt->fetchColumn() ?: self::STATE_NORMAL;
    }

    /**
     * Set state manual / otomatis
     */
    public static function setState(PDO $pdo, string $ip, string $state): void {
        $stmt = $pdo->prepare("
            INSERT INTO ip_reputation (ip, state, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE state=VALUES(state), updated_at=NOW()
        ");
        $stmt->execute([$ip, $state]);
    }

    /**
     * Update reputasi berdasarkan traffic
     */
    public static function update(PDO $pdo, array $d): void {

        $ip = $d['ip'];
        $delta = 0;

        $status = intval($d['status'] ?? 0);
        $ua     = strtolower($d['user_agent'] ?? '');
        $path   = $d['path'] ?? '';

        // Heuristik reputasi
        if ($status === 401) $delta += 1;
        if ($status >= 500)  $delta += 1.5;
        if (str_contains($ua, 'bot')) $delta += 0.5;
        if (str_contains($path, '.env') || str_contains($path, 'wp-admin')) $delta += 2;

        // Simpan skor
        $stmt = $pdo->prepare("
            INSERT INTO reputation_scores (ts, ip, delta, reason)
            VALUES (NOW(), ?, ?, ?)
        ");
        $stmt->execute([$ip, $delta, 'auto']);

        // Update total score
        $stmt = $pdo->prepare("
            INSERT INTO ip_reputation (ip, score, state, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                score = score + VALUES(score),
                updated_at = NOW()
        ");
        $stmt->execute([$ip, $delta, self::STATE_NORMAL]);

        // Escalation otomatis
        self::evaluate($pdo, $ip);
    }

    /**
     * Evaluasi state berdasarkan score
     */
    public static function evaluate(PDO $pdo, string $ip): void {
        $stmt = $pdo->prepare("SELECT score, state FROM ip_reputation WHERE ip=?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return;

        $score = floatval($row['score']);
        $state = $row['state'];

        $newState = $state;

        if ($score >= 10)       $newState = self::STATE_MALICIOUS;
        elseif ($score >= 6)   $newState = self::STATE_QUARANTINE;
        elseif ($score >= 3)   $newState = self::STATE_SUSPICIOUS;
        else                   $newState = self::STATE_NORMAL;

        if ($newState !== $state) {
            self::setState($pdo, $ip, $newState);
        }
    }

    /**
     * Turunkan reputasi (cooldown)
     */
    public static function decay(PDO $pdo, int $minutes = 60): void {
        $stmt = $pdo->prepare("
            UPDATE ip_reputation
            SET score = GREATEST(score - 1, 0)
            WHERE updated_at < NOW() - INTERVAL ? MINUTE
        ");
        $stmt->execute([$minutes]);
    }

    /**
     * Ambil score reputasi
     */
    public static function getScore(PDO $pdo, string $ip): float {
        $stmt = $pdo->prepare("SELECT score FROM ip_reputation WHERE ip=?");
        $stmt->execute([$ip]);
        return floatval($stmt->fetchColumn() ?: 0);
    }

}
