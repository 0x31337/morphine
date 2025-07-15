<?php
require 'WebSocket.php';

use Ratchet\Client\Connector;
use React\EventLoop\LoopInterface;
use WebSocket\Client;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use React\Promise\Promise;

class ZergController
{
    protected static \Morphine\Engine\Database $dbConnection;
    protected static LoopInterface $loop;
    public static function set()
    {
        self::$dbConnection = new \Morphine\Engine\Database();
        self::$loop = Factory::create();
    }

    // This is a demo method that showcases an example of usage of this class
    public static function checkDatabaseForCommands() {
        $loop = Factory::create();
        $connector = new Connector($loop);
        $libZergSocket = new libZergSocket(self::$dbConnection);
        $connector('ws://localhost:8080') // Replace with your WebSocket server URL
        ->then(function(WebSocket $conn) use ($loop, $libZergSocket) {
            // Establish connection and start periodic timer
            $loop->addPeriodicTimer(0.2, function () use ($conn, $libZergSocket) {
                // Step 1: Send request to get active clients
                $conn->send("ZController_Check");
                echo ".";
            });

        }, function (\Exception $e) use ($loop) {
            echo "Could not connect to WebSocket server: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }
}
ZergController::set();
ZergController::checkDatabaseForCommands();