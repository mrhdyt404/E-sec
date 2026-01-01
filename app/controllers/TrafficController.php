<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../services/GeoIPService.php';
require __DIR__ . '/../services/ReputationService.php';
require __DIR__ . '/../services/BlockchainService.php';
require __DIR__ . '/../services/AnomalyService.php';
require __DIR__ . '/../services/RateLimitService.php';
require __DIR__ . '/../services/AlertService.php';
require __DIR__ . '/../services/BehaviorService.php';
require __DIR__ . '/../services/MLAnomalyService.php';
require __DIR__ . '/../services/AutoBlockService.php';
require __DIR__ . '/../services/FirewallService.php';
require __DIR__ . '/../services/BlockAuditService.php';
require __DIR__ . '/../services/ThresholdService.php';
require __DIR__ . '/../services/AuditService.php';


class TrafficController {

    public function collect() {
        header('Content-Type: application/json');

        $headers = getallheaders();
        $apiKey = trim(str_ireplace('Bearer', '', $headers['Authorization'] ?? ''));

        $pdo = Database::connect();

        // 🔐 Auth
        $stmt = $pdo->prepare("SELECT id FROM api_clients WHERE api_key=? AND active=1");
        $stmt->execute([$apiKey]);
        if (!$stmt->fetch()) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            exit;
        }

        // ⚡ Rate limit
        RateLimitService::check($pdo, $apiKey);

        // 📥 Payload
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['ip'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid payload"]);
            exit;
        }

        // 🌍 GeoIP
        $geo = GeoIPService::lookup($pdo, $data['ip']);
        $data['country'] = $geo['country'] ?? null;
        $data['city']    = $geo['city'] ?? null;

        $ips = is_array($data['ip']) ? $data['ip'] : [$data['ip']];
        $data['blocked'] = false;

        foreach ($ips as $ip) {
            $d = $data;
            $d['ip'] = $ip;

            // 1️⃣ Behavior
            $behaviorScore = BehaviorService::update($pdo, $d);

            // 2️⃣ Reputation & anomaly
            $state = ReputationService::getState($pdo, $ip);
            ReputationService::update($pdo, $d);
            $user = $data['user'] ?? 'system';
            AuditService::log($pdo, $ip, 'investigate', 'view',  $user);

            // ================= SYNC TO THREATS =================
            AnomalyService::check($pdo, $d);
            if (class_exists('ThreatService')) {
                ThreatService::syncThreats($pdo);
            }

            // 3️⃣ ML
            $mlScore = MLAnomalyService::score($d);

            // 4️⃣ Signals
            $isBruteforce = FirewallService::maybeBlock($pdo, $ip); // signal only
            $isScanner    = BehaviorService::isScanner($d);
            $is500Flood   = BehaviorService::isServerErrorFlood($pdo, $ip);
            $isBehavior   = BehaviorService::isMalicious($pdo, $ip);

            // 5️⃣ AutoBlock decision
            $autoBlock = AutoBlockService::shouldBlock($pdo, $ip, $behaviorScore, $mlScore);

            // 6️⃣ Final malicious decision
            $isMalicious = (
                $isBehavior ||
                $autoBlock ||
                $isScanner ||
                $is500Flood ||
                $isBruteforce ||
                ($mlScore < -0.5)
            );

            if ($isMalicious) {
                FirewallService::block($pdo, $ip, 3600, 'malicious');
                AutoBlockService::block($pdo, $ip, $behaviorScore, 'malicious', 'auto');
                $data['blocked'] = true;
            }

            // 7️⃣ Reputation escalation (only if not yet blocked)
            if (!$isMalicious) {
                $score = $behaviorScore + max(0, -$mlScore);

                if ($state === 'normal' && $score > 2) {
                    ReputationService::setState($pdo, $ip, 'suspicious');
                }

                if ($state === 'suspicious' && $score > 4) {
                    ReputationService::setState($pdo, $ip, 'quarantine');
                }

                if ($state === 'quarantine' && $score > 6) {
                    FirewallService::block($pdo, $ip, 3600, 'escalation');
                }
            }
        }

        // 💾 Save traffic log
        $stmt = $pdo->prepare("
            INSERT INTO traffic_logs 
            (ts, ip, method, path, status, response_time_ms, bytes, user_agent, referrer, server, country, city)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['timestamp'] ?? date('Y-m-d H:i:s'),
            implode(',', $ips),
            $data['method'] ?? '',
            $data['path'] ?? '',
            $data['status'] ?? 0,
            $data['response_time_ms'] ?? null,
            $data['bytes'] ?? null,
            $data['user_agent'] ?? null,
            $data['referrer'] ?? null,
            $data['server'] ?? null,
            $data['country'],
            $data['city']
        ]);

        // 🚀 Push WS
        $data['internal'] = true;
        foreach ($ips as $ip) {
            if (ThrottleService::allow($pdo, $ip)) {
                $this->pushRealtime($data);
                break;
            }
        }

        // 🚨 ML alert
        if ($mlScore < -0.4) {
            AlertService::sendFCM("🚨 ML Anomaly", "{$data['ip']} {$data['path']} score=$mlScore");
        }

        echo json_encode(["status" => "ok", "blocked" => $data['blocked']]);
        exit;
    }

    private function pushRealtime(array $payload) {
        $ch = curl_init("http://127.0.0.1:8080/internal/broadcast");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 500,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

class ThrottleService {
    const INTERVAL = 1;

    public static function allow(PDO $pdo, string $ip): bool {
        $now = time();

        $stmt = $pdo->prepare("SELECT last_push FROM ws_throttle WHERE ip=?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && ($now - $row['last_push']) < self::INTERVAL) {
            return false;
        }

        $stmt = $pdo->prepare("
            INSERT INTO ws_throttle (ip,last_push)
            VALUES (?,?)
            ON DUPLICATE KEY UPDATE last_push=VALUES(last_push)
        ");
        $stmt->execute([$ip, $now]);

        return true;
    }
}


// block jika 401 >= 5 kali dalam 60 detik
// class FirewallService {

//     public static function maybeBlock(PDO $pdo, string $ip): void {

//         $stmt = $pdo->prepare("
//             SELECT COUNT(*) FROM traffic_logs
//             WHERE ip=? AND status=401 AND ts > NOW() - INTERVAL 1 MINUTE
//         ");
//         $stmt->execute([$ip]);
//         $count = $stmt->fetchColumn();

//         if ($count >= 5) {
//             exec("sudo ipset add blacklist $ip timeout 3600");
//         }
//     }
// }



