<?php

class FirewallService {

    public static function isTrusted(PDO $pdo, string $ip): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM trusted_ips WHERE ip=?");
        $stmt->execute([$ip]);
        return (bool)$stmt->fetchColumn();
    }

    public static function isBlocked(string $ip): bool {
        exec("/usr/bin/sudo /sbin/ipset test blacklist " . escapeshellarg($ip), $out, $code);
        return $code === 0;
    }

    public static function block(PDO $pdo, string $ip, int $timeout = 3600, string $reason = 'auto'): void {

        if (self::isTrusted($pdo, $ip)) return;
        if (self::isBlocked($ip)) return;

        $cmd = "/usr/bin/sudo /usr/local/bin/firewall-block.sh " . escapeshellarg($ip) . " " . intval($timeout);
        exec($cmd . " 2>&1", $out, $code);

        if ($code !== 0) {
            error_log("Firewall block failed for $ip: " . implode("\n", $out));
        } else {
            error_log("Firewall blocked IP $ip ($reason) for {$timeout}s");
        }
    }

    public static function unblock(string $ip): void {
        if (!self::isBlocked($ip)) return;
        exec("/usr/bin/sudo /sbin/ipset del blacklist " . escapeshellarg($ip) . " 2>&1");
    }

    /**
     * Block hanya jika brute-force nyata
     */
    public static function maybeBlock(PDO $pdo, string $ip, int $threshold = 5, int $windowSec = 60): bool {

        if (self::isTrusted($pdo, $ip)) return false;

        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM traffic_logs
            WHERE ip=? AND status=401 AND ts > NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([$ip, $windowSec]);
        $count = (int)$stmt->fetchColumn();

        return $count >= $threshold;
    }
}
