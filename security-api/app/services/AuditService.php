<?php
class AuditService {
  public static function log(PDO $pdo,$ip,$action,$reason,$actor){
    $pdo->prepare("INSERT INTO audit_logs VALUES (NULL,NOW(),?,?,?,?)")
        ->execute([$ip,$action,$reason,$actor]);
  }

  public static function latest(PDO $pdo,$n){
    return $pdo->query("SELECT ts,ip,action,reason,actor FROM audit_logs ORDER BY ts DESC LIMIT $n")->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getByIp(PDO $pdo,$ip){
    $s=$pdo->prepare("SELECT ts,action,reason,actor FROM audit_logs WHERE ip=? ORDER BY ts DESC");
    $s->execute([$ip]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
  }
}
?>
