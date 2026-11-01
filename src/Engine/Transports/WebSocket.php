<?php

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

namespace localzet\SocketIO\Engine\Transports;

use localzet\SocketIO\Debug;
use localzet\SocketIO\Engine\Parser;
use localzet\SocketIO\Engine\Transport;

class WebSocket extends Transport
{
    public $writable = true;
    public $supportsFraming = true;
    public $supportsBinary = true;
    public $name = 'websocket';
    public $socket = null;

    public function __construct($req)
    {
        $this->socket = $req->connection;
        $this->socket->onMessage = [$this, 'onData2'];
        $this->socket->onClose = [$this, 'onClose'];
        $this->socket->onError = [$this, 'onError2'];
        Debug::debug('WebSocket __construct');
    }

    public function __destruct()
    {
        Debug::debug('WebSocket __destruct');
    }

    public function onData2($connection, $data): void
    {
        call_user_func([$this, 'parent::onData'], $data);
    }

    public function onError2($conection, $code, $msg): void
    {
        call_user_func([$this, 'parent::onClose'], $code, $msg);
    }

    public function send(array $packets): void
    {
        foreach ($packets as $packet) {
            $data = Parser::encodePacket($packet);
            if ($this->socket) {
                $this->socket->send($data);
                $this->emit('drain');
            }
        }
    }

    public function doClose(callable $fn = null): void
    {
        if ($this->socket) {
            $this->socket->close();
            $this->socket = null;
            if (!empty($fn)) {
                call_user_func($fn);
            }
        }
    }
}
