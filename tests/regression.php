<?php

declare(strict_types=1);

/**
 * @package     Localzet Socket.IO
 * @link        https://github.com/localzet/SocketIO
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use localzet\Server\Connection\TcpConnection;
use localzet\Server\Events\Windows;
use localzet\Server\Protocols\ProtocolInterface;
use localzet\Server\Protocols\Socketio;
use localzet\SocketIO\Engine\Protocols\WebSocket\RFC6455;

if (!class_exists('Emitter') || !is_subclass_of(RFC6455::class, ProtocolInterface::class)) {
    throw new RuntimeException('Legacy classes cannot load with the supported Server.');
}
if (!is_subclass_of(Socketio::class, ProtocolInterface::class)) {
    throw new RuntimeException('SocketIO listener protocol adapter is not registered.');
}
$socketIo = new \localzet\SocketIO\SocketIO(23456);
if (!$socketIo->worker instanceof localzet\Server || $socketIo->worker->protocol !== Socketio::class) {
    throw new RuntimeException('SocketIO listener did not resolve the Server 7 protocol.');
}
echo "Server 7 SocketIO listener registration passed.\n";
$pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
if ($pair === false) {
    throw new RuntimeException('Cannot create test socket pair.');
}
$connection = new TcpConnection(new Windows(), $pair[0], '127.0.0.1:9000');
$connection->websocketCurrentFrameLength = 0;
$connection->websocketDataBuffer = '';
$mask = "\x01\x02\x03\x04";
$payload = 'hi';
$frame = "\x81\x82" . $mask . ($payload[0] ^ $mask[0]) . ($payload[1] ^ $mask[1]);
try {
    if (RFC6455::input(substr($frame, 0, 3), $connection) !== 0) {
        throw new RuntimeException('Partial frame must wait for more data.');
    }
    if (RFC6455::input($frame, $connection) !== strlen($frame) || RFC6455::decode($frame, $connection) !== 'hi') {
        throw new RuntimeException('Complete masked frame must decode unchanged.');
    }
    echo "SocketIO class compatibility and masked-frame regression passed.\n";
} finally {
    $connection->close();
    fclose($pair[1]);
}
