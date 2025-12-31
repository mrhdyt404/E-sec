<?php
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../ws-server-instance.php';

$data = json_decode(file_get_contents("php://input"), true);
$trafficSocket->broadcast($data);
echo "ok";
