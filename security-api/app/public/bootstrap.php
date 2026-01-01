<?php
require __DIR__ . '/../core/Database.php';

define('BASE_PATH', realpath(__DIR__ . '/../')); 

require BASE_PATH . '/config/config.php';
require BASE_PATH . '/services/StatsService.php';
require BASE_PATH . '/services/FirewallService.php';
require BASE_PATH . '/services/AuditService.php';
require BASE_PATH . '/services/ReputationService.php';
require BASE_PATH . '/services/ThreatService.php';
require BASE_PATH . '/services/BehaviorService.php';

// ===== Inisialisasi PDO =====
$pdo = Database::connect();
