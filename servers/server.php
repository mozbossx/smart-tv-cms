<?php

require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\ContentHandler;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=smart_tv_cms_db', 'root', 'Mozar@123456');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$contentHandler = new ContentHandler($pdo);

// Create WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $contentHandler
        )
    ),
    8081,
    '127.0.0.1' // Bind to localhost only
);

echo "WebSocket Server Started on port 8081\n";
$server->run();