<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../bootstrap.php';
require '../../config/config.php';

header('Content-Type: application/json');

echo json_encode([
  'requests'    => StatsService::requests24h($pdo),
  'blocked'     => FirewallService::countBlocked($pdo),
  'suspicious'  => ThreatService::countSuspicious($pdo), 
  'anomaly'     => ThreatService::countAnomaly($pdo),    
  'avg_latency' => StatsService::avgLatency($pdo),
]);