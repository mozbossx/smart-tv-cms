<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\ContentHandler;
use Ratchet\Wamp\WampServer;
use Ratchet\Wamp\TopicManager;
use Ratchet\ConnectionInterface;

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=smart_tv_cms_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$contentHandler = new ContentHandler($pdo);

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $contentHandler
        )
    ),
    8081
);

$server->run();