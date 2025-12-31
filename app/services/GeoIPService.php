<?php
class GeoIPService {
    public static function lookup($pdo, $ip) {
        $stmt = $pdo->prepare("SELECT country, city FROM geoip_cache WHERE ip=?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        if ($row) return $row;

        // dummy (replace with MaxMind)
        $country = 'ID';
        $city = 'Unknown';

        $pdo->prepare("INSERT INTO geoip_cache (ip,country,city) VALUES (?,?,?)")
            ->execute([$ip,$country,$city]);

        return ['country'=>$country,'city'=>$city];
    }
}
