<?php
// Script untuk menguji broadcast ke server

$testData = [
    'ip' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
    'status' => rand(200, 500),
    'method' => ['GET', 'POST', 'PUT', 'DELETE'][rand(0, 3)],
    'path' => '/api/' . ['users', 'posts', 'auth', 'data'][rand(0, 3)],
    'user_agent' => 'Test-Bot/1.0',
    'count' => rand(1, 10)
];

$url = 'http://127.0.0.1:8080/internal/broadcast';

echo "📤 Testing broadcast to: $url\n";
echo "📊 Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "❌ Error: " . curl_error($ch) . "\n";
} else {
    echo "✅ HTTP Code: $httpCode\n";
    echo "📥 Response: $response\n";
}

curl_close($ch);
?>