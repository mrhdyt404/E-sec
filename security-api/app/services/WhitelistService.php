<?php
    class WhitelistService {

        public static function isWhitelisted(PDO $pdo, string $ip): bool {
            $stmt = $pdo->prepare("SELECT 1 FROM whitelisted_ips WHERE ip=?");
            $stmt->execute([$ip]);
            return (bool)$stmt->fetchColumn();
        }

        public static function remove(PDO $pdo, string $ip): void {
            $stmt = $pdo->prepare("DELETE FROM whitelisted_ips WHERE ip=?");
            $stmt->execute([$ip]);
        }

    }
?>