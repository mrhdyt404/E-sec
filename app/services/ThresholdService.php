<?php

class ThresholdService {

    /**
     * Ambil threshold aktif
     */
    public static function get(PDO $pdo, string $metric): float {

        $stmt = $pdo->prepare("SELECT value FROM adaptive_thresholds WHERE metric=?");
        $stmt->execute([$metric]);
        $v = $stmt->fetchColumn();

        return $v !== false ? floatval($v) : self::default($metric);
    }

    /**
     * Default jika belum ada di DB
     */
    public static function default(string $metric): float {
        return match($metric) {
            'behavior' => 3.0,
            'ml'       => -0.5,
            default    => 0.0
        };
    }

    /**
     * Naikkan threshold jika sistem ramai
     */
    public static function raise(PDO $pdo, string $metric, float $delta): void {
        self::set($pdo, $metric, self::get($pdo, $metric) + $delta);
    }

    /**
     * Turunkan threshold jika sistem terlalu permisif
     */
    public static function lower(PDO $pdo, string $metric, float $delta): void {
        self::set($pdo, $metric, self::get($pdo, $metric) - $delta);
    }

    /**
     * Simpan threshold
     */
    public static function set(PDO $pdo, string $metric, float $value): void {

        $stmt = $pdo->prepare("
            INSERT INTO adaptive_thresholds (metric,value,updated_at)
            VALUES (?,?,NOW())
            ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=NOW()
        ");
        $stmt->execute([$metric,$value]);
    }

}
