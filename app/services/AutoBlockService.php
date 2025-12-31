<?php

class AutoBlockService {

    const BLOCK_TIME = 3600; // 1 jam

    public static function block(PDO $pdo, string $ip, float $score, string $reason, string $source = 'ml'): void {

        // Cek sudah diblock?
        $stmt = $pdo->prepare("SELECT id FROM blocked_ips WHERE ip = ? AND active = 1");
        $stmt->execute([$ip]);
        if ($stmt->fetch()) return;

        // Simpan DB
        $stmt = $pdo->prepare("
            INSERT INTO blocked_ips (ip, reason, score, source, expires_at)
            VALUES (?, ?, ?, ?, NOW() + INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip, $reason, $score, $source]);

        // System firewall
        exec("sudo ipset add blacklist {$ip} timeout " . self::BLOCK_TIME);

        // Alert
        if (class_exists('AlertService')) {
            AlertService::sendFCM("⛔ IP Blocked", "$ip blocked ($reason)");
            AlertService::sendWA("⛔ IP Blocked: $ip ($reason)");
        }
    }

    public static function unblockExpired(PDO $pdo): void {
        $ips = $pdo->query("
            SELECT ip FROM blocked_ips 
            WHERE active = 1 AND expires_at < NOW()
        ")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ips as $ip) {
            exec("sudo ipset del blacklist {$ip}");
        }

        $pdo->query("
            UPDATE blocked_ips SET active = 0 WHERE expires_at < NOW()
        ");
    }
}
    