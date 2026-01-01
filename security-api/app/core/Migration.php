<?php
class Migration {

    public static function run() {
        $pdo = Database::connect();
        $path = dirname(__DIR__) . '/migrations';

        $files = glob($path . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            echo "Running " . basename($file) . PHP_EOL;
            $sql = file_get_contents($file);
            $pdo->exec($sql);
        }
    }
}
