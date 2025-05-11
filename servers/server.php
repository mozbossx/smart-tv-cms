<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\ContentHandler;
use React\EventLoop\Loop;
use Ratchet\Wamp\WampServer;
use Ratchet\Wamp\TopicManager;
use Ratchet\ConnectionInterface;

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_TIMEOUT => 28800,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=smart_tv_cms_db', 'root', 'Mozar@123456', $pdoOptions);
    $contentHandler = new ContentHandler($pdo);

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                $contentHandler
            )
        ),
        8081
    );
    echo "Server is running on port 8081 \n";
    $server->run();

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}