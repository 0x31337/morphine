<?php

use Morphine\Engine\WebSocketServer\WebSocket;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as ReactServer;

require 'WebSocket.php';

// Starting the WebSocket server
$loop = LoopFactory::create();
$webSock = new ReactServer('0.0.0.0:8080', $loop);
$wsServer = new WebSocket();

$server = new IoServer(
    new HttpServer(
        new WsServer(
            $wsServer
        )
    ),
    $webSock,
    $loop
);

echo "WebSocket server started on ws://localhost:8080\n";

$server->run();