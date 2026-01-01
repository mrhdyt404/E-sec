<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class RateLimitService {
    const LIMIT = 60;       // max 60 request
    const WINDOW = 60;      // per 60 detik

    public static function check(PDO $pdo, string $apiKey): void {
        $now = time();
        $window = floor($now / self::WINDOW) * self::WINDOW;

        // Atomic upsert
        $stmt = $pdo->prepare("
            INSERT INTO api_rate_limits (api_key, window_start, count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE count = count + 1
        ");
        $stmt->execute([$apiKey, $window]);

        // Read back count
        $stmt = $pdo->prepare("
            SELECT count FROM api_rate_limits
            WHERE api_key = ? AND window_start = ?
        ");
        $stmt->execute([$apiKey, $window]);
        $count = (int) $stmt->fetchColumn();

        if ($count > self::LIMIT) {
            http_response_code(429);
            exit(json_encode([
                "error" => "Rate limit exceeded",
                "limit" => self::LIMIT,
                "window_sec" => self::WINDOW
            ]));
        }
    }
}
