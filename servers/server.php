<?php

require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\ContentHandler;
use Ratchet\Server\SecureServer;
use React\Socket\Server as ReactServer;
use React\Socket\SecureServer as ReactSecureServer;

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=smart_tv_cms_db', 'root', 'Mozar@123456');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$contentHandler = new ContentHandler($pdo);

// Set up React PHP server
$loop = React\EventLoop\Factory::create();
$reactServer = new ReactServer('0.0.0.0:8081', $loop);

// SSL context options
$sslContext = [
    'local_cert' => '/etc/letsencrypt/live/usccms.online/fullchain.pem',
    'local_pk' => '/etc/letsencrypt/live/usccms.online/privkey.pem',
    'allow_self_signed' => false,
    'verify_peer' => false, // Changed to false for WebSocket
    'verify_peer_name' => false, // Added to disable peer name verification
    'security_level' => 1, // Added to set minimum security level
];
$secureServer = new ReactSecureServer($reactServer, $loop, $sslContext);

$server = new IoServer(
    new HttpServer(
        new WsServer(
            $contentHandler
        )
    ),
    $secureServer,
    $loop
);

$server->run();
