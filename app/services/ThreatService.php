<?php
class ThreatService {

  // Ambil daftar anomali aktif (flagged = 1)
  public static function active(PDO $pdo){
    return $pdo->query("
      SELECT ip, type AS state, score, description AS reason, ts AS last_seen
      FROM anomalies
      WHERE flagged=1
      ORDER BY ts DESC
      LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
  }

  // Hitung suspicious + anomaly + http_error (non-normal)
  public static function countSuspicious(PDO $pdo){
    return $pdo->query("
        SELECT COUNT(*) 
        FROM anomalies 
        WHERE flagged=1 AND type != 'normal'
    ")->fetchColumn();
  }

  public static function countAnomaly(PDO $pdo){
    return $pdo->query("
        SELECT COUNT(*) 
        FROM anomalies 
        WHERE flagged=1 AND (type='ml_anomaly' OR type='http_error')
    ")->fetchColumn();
  }

  /**
   * Insert flagged anomaly ke tabel threats
   */
  public static function syncThreats(PDO $pdo){
      $anomalies = $pdo->query("
          SELECT ip, type, score, description, ts
          FROM anomalies
          WHERE flagged=1
          ORDER BY ts DESC
          LIMIT 200
      ")->fetchAll(PDO::FETCH_ASSOC);

      $stmtGet = $pdo->prepare("SELECT state FROM threats WHERE ip=?");

      $stmtUp = $pdo->prepare("
          INSERT INTO threats (ip, state, score, reason, last_seen, created_at)
          VALUES (?, ?, ?, ?, ?, NOW())
          ON DUPLICATE KEY UPDATE
              state = VALUES(state),
              score = VALUES(score),
              reason = VALUES(reason),
              last_seen = VALUES(last_seen)
      ");

      foreach ($anomalies as $a) {
          $newState = match($a['type']) {
              'ml_anomaly', 'http_error' => 'anomaly',
              'normal' => 'cleared',
              default => 'suspicious',
          };

          $stmtGet->execute([$a['ip']]);
          $current = $stmtGet->fetchColumn();

          // Jangan downgrade severity
          if ($current === 'anomaly' && $newState === 'cleared') continue;
          if ($current === 'anomaly' && $newState === 'suspicious') continue;
          if ($current === 'suspicious' && $newState === 'cleared') continue;

          $stmtUp->execute([
              $a['ip'],
              $newState,
              $a['score'],
              $a['description'],
              $a['ts']
          ]);
      }
  }
}
?>
