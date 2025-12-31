<?php
class FirewallService {

    public static function isTrusted(PDO $pdo, string $ip): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM trusted_ips WHERE ip=?");
        $stmt->execute([$ip]);
        return (bool)$stmt->fetchColumn();
    }

    public static function block(PDO $pdo, string $ip, int $timeout = null): void {
        if (self::isTrusted($pdo, $ip)) {
            error_log("Skip block trusted IP $ip");
            return;
        }

        $cmd = "/usr/bin/sudo /usr/local/bin/firewall-block.sh " . escapeshellarg($ip);
        if ($timeout !== null) {
            $cmd .= " " . intval($timeout);
        }

        exec($cmd . " 2>&1", $out, $code);
        if ($code !== 0) {
            error_log("Firewall block failed for $ip: " . implode("\n", $out));
        }
    }

    public static function unblock(string $ip): void {
        exec("/usr/bin/sudo /sbin/ipset test blacklist " . escapeshellarg($ip), $out, $code);
        if ($code !== 0) return;
        exec("/usr/bin/sudo /sbin/ipset del blacklist " . escapeshellarg($ip) . " 2>&1", $out, $code);
    }

    // 🔥 Method baru: maybeBlock
    public static function maybeBlock(PDO $pdo, string $ip, int $threshold = 5, int $windowSec = 60) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM traffic_logs
            WHERE ip=? AND status=401 AND ts > NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([$ip, $windowSec]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= $threshold) {
            self::block($pdo, $ip, 3600); // blok 1 jam
            error_log("Auto-block IP $ip due to $count 401 errors in last $windowSec seconds");
        }
    }
}

