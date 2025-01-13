<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\ContentHandler;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=smart_tv_cms_db', 'root', 'Mozar@123456');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$contentHandler = new ContentHandler($pdo);

// Create event loop
$loop = Factory::create();

// Bind to the server's IP address
$webSock = new SocketServer('usccms.online:8081', $loop);

// Create Ratchet server
$server = new IoServer(
    new HttpServer(
        new WsServer(
            $contentHandler
        )
    ),
    $webSock
);

echo "WebSocket Server Started on usccms.online:8081\n";
$loop->run();