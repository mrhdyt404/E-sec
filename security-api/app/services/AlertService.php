<?php

class AlertService {

    public static function sendFCM(string $title, string $message): void {
        $key = getenv('FCM_KEY') ?: 'YOUR_FCM_KEY';

        $payload = [
            "to" => "/topics/security",
            "notification" => [
                "title" => $title,
                "body"  => $message
            ]
        ];

        $ch = curl_init("https://fcm.googleapis.com/fcm/send");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: key=$key",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 2
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public static function sendWA(string $message): void {
        $waEndpoint = getenv('WA_ENDPOINT');
        if (!$waEndpoint) return;

        curl_setopt_array($ch = curl_init($waEndpoint), [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query(['text' => $message]),
            CURLOPT_TIMEOUT => 2
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
