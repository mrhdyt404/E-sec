<?php

class AutoBlockService {

    const BLOCK_TIME = 3600;

    const BEHAVIOR_THRESHOLD = 8;
    const ML_THRESHOLD = -0.4;

    // Tentukan apakah IP perlu diblock
    public static function shouldBlock(PDO $pdo, string $ip, float $behaviorScore, ?float $mlScore): bool {

        // Sudah diblock aktif?
        $stmt = $pdo->prepare("SELECT 1 FROM blocked_ips WHERE ip = ? AND active = 1");
        $stmt->execute([$ip]);
        if ($stmt->fetchColumn()) return true;

        // Behavior berbahaya
        if ($behaviorScore >= self::BEHAVIOR_THRESHOLD) return true;

        // ML anomali ekstrem
        if ($mlScore !== null && $mlScore <= self::ML_THRESHOLD) return true;

        return false;
    }

    // Simpan block ke DB + alert (firewall di-handle FirewallService)
    public static function block(PDO $pdo, string $ip, float $score, string $reason, string $source = 'auto'): void {

        $stmt = $pdo->prepare("SELECT 1 FROM blocked_ips WHERE ip=? AND active=1");
        $stmt->execute([$ip]);
        if ($stmt->fetchColumn()) return;

        $stmt = $pdo->prepare("
            INSERT INTO blocked_ips (ip, reason, score, source, expires_at, active)
            VALUES (?, ?, ?, ?, NOW() + INTERVAL 1 HOUR, 1)
        ");
        $stmt->execute([$ip, $reason, $score, $source]);

        if (class_exists('AlertService')) {
            AlertService::sendFCM("⛔ IP Blocked", "$ip blocked ($reason)");
            AlertService::sendWA("⛔ IP Blocked: $ip ($reason)");
        }
    }

    // Unblock expired IP
    public static function unblockExpired(PDO $pdo): void {

        $ips = $pdo->query("
            SELECT ip FROM blocked_ips 
            WHERE active = 1 AND expires_at < NOW()
        ")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ips as $ip) {
            FirewallService::unblock($ip);
        }

        $pdo->query("UPDATE blocked_ips SET active = 0 WHERE expires_at < NOW()");
    }
}
