<?php
require '../bootstrap.php';

$pdo = Database::connect();
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ts','ip','action','reason','actor']);

foreach($pdo->query("SELECT ts, ip, action, reason, actor FROM audit_trail ORDER BY ts DESC") as $row){
    fputcsv($output, $row);
}
fclose($output);
