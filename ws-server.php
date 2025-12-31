<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\SocketServer;
use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

// ================= LOGGING FUNCTION =================
function logMsg($msg) {
    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    echo $line;
    file_put_contents(__DIR__ . '/log.txt', $line, FILE_APPEND);
}

// ================= WEBSOCKET CLASS =================
class TrafficSocket implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        logMsg("WebSocket server constructed");
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        logMsg("WS Client connected (id={$conn->resourceId}, total=" . count($this->clients) . ")");
        // Send welcome
        $conn->send(json_encode([
            'type' => 'system',
            'message' => 'Connected to Security Dashboard',
            'time' => date('H:i:s'),
            'total_clients' => count($this->clients)
        ]));
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        logMsg("WS Client disconnected (id={$conn->resourceId}, remaining=" . count($this->clients) . ")");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        logMsg("WS Message from client {$from->resourceId}: $msg");
        $data = json_decode($msg, true);
        if ($data && isset($data['type']) && $data['type'] === 'ping') {
            $from->send(json_encode(['type' => 'pong', 'time' => time()]));
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        logMsg("WS ERROR [{$conn->resourceId}]: {$e->getMessage()}");
        $conn->close();
    }

    public function broadcast(array $data) {
        $payload = json_encode(array_merge($data, [
            'time' => date('H:i:s'),
            'count' => $data['count'] ?? 1
        ]));

        $clientCount = count($this->clients);
        logMsg("Broadcasting to $clientCount clients: " . substr($payload, 0, 200) . "...");

        foreach ($this->clients as $client) {
            try {
                $client->send($payload);
            } catch (\Exception $e) {
                logMsg("Failed to send to client: " . $e->getMessage());
            }
        }

        return $clientCount;
    }
}

// ================= SETUP EVENT LOOP =================
$loop = LoopFactory::create();

// ================= CREATE WEBSOCKET SERVER (PORT 8081) =================
$webSocket = new TrafficSocket();
$wsServer = new IoServer(
    new HttpServer(new WsServer($webSocket)),
    new SocketServer('0.0.0.0:8081', [], $loop),
    $loop
);
logMsg("✅ WebSocket server running on ws://0.0.0.0:8081");

// ================= CREATE HTTP SERVER (PORT 8080) =================
$httpServer = new ReactHttpServer(function (ServerRequestInterface $request) use ($webSocket) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();
    logMsg("HTTP {$method} {$path}");

    // POST /internal/broadcast
    if ($path === '/internal/broadcast' && $method === 'POST') {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        if ($data) {
            $clientCount = $webSocket->broadcast($data);
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'status' => 'ok',
                    'message' => 'Broadcast successful',
                    'clients_reached' => $clientCount,
                    'data' => $data
                ])
            );
        }
        return new Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'Invalid JSON payload'])
        );
    }

    // GET /internal/broadcast (testing)
    if ($path === '/internal/broadcast' && $method === 'GET') {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'status' => 'info',
                'message' => 'Send POST request with JSON data to broadcast',
                'example' => ['ip'=>'192.168.1.100','status'=>200,'method'=>'GET','path'=>'/api/test']
            ])
        );
    }

    // Health check
    if ($path === '/health') {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'services' => [
                    'websocket' => 'running on port 8081',
                    'http' => 'running on port 8080'
                ]
            ])
        );
    }

    // 404
    return new Response(
        404,
        ['Content-Type' => 'application/json'],
        json_encode(['error' => 'Not Found', 'path' => $path])
    );
});

// HTTP server on 8080
$httpSocket = new SocketServer('0.0.0.0:8080', [], $loop);
$httpServer->listen($httpSocket);

logMsg("✅ HTTP server running on http://0.0.0.0:8080");
logMsg("📡 Internal broadcast endpoint: http://127.0.0.1:8080/internal/broadcast");
logMsg("🔌 WebSocket endpoint: ws://127.0.0.1:8081");
logMsg("🩺 Health check: http://127.0.0.1:8080/health");

$loop->run();
