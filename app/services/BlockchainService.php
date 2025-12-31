<?php
class BlockchainService {
    public static function add($pdo, $data) {
        $prev = $pdo->query("SELECT hash FROM blocks ORDER BY id DESC LIMIT 1")->fetchColumn() ?? '0';
        $dataHash = hash('sha256', json_encode($data));
        $hash = hash('sha256', $prev.$dataHash);

        $stmt = $pdo->prepare("INSERT INTO blocks (prev_hash,hash,data_hash,ts) VALUES (?,?,?,NOW())");
        $stmt->execute([$prev,$hash,$dataHash]);
    }
}
