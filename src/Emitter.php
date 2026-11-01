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


class Emitter
{
    protected $_rooms = array();

    protected $_flags = array();

    protected $_remoteIp = '';

    protected $_remotePort = 2206;

    protected $_key = 'socket.io#/#';

    protected $_client = null;

    protected $_context = null;

    public function __construct($ip = '127.0.0.1', $port = 2206, $context = null)
    {
        $this->_remoteIp = $ip;
        $this->_remotePort = $port;
        $this->_context = $context;
        $this->connect();
    }

    protected function connect()
    {
        if (is_array($this->_context)) {
            $context = stream_context_create($this->_context);
            $this->_client = stream_socket_client("tcp://{$this->_remoteIp}:{$this->_remotePort}", $errno, $errmsg, 3, STREAM_CLIENT_CONNECT, $context);
        } else {
            $this->_client = stream_socket_client("tcp://{$this->_remoteIp}:{$this->_remotePort}", $errno, $errmsg, 3);
        }

        if (!$this->_client) {
            throw new \Exception($errmsg);
        }
    }

    public function __get($name)
    {
        if ($name === 'broadcast') {
            $this->_flags['broadcast'] = true;
            return $this;
        }
        return null;
    }

    public function to($name)
    {
        if (!isset($this->_rooms[$name])) {
            $this->_rooms[$name] = $name;
        }
        return $this;
    }

    public function in($name)
    {
        return $this->to($name);
    }

    public function emit($ev)
    {
        if (feof($this->_client)) {
            $this->connect();
        }

        $args = func_get_args();

        $parserType = 2;// Parser::EVENT

        $packet = array('type' => $parserType, 'data' => $args, 'nsp' => '/');

        $buffer = serialize(array(
                'type' => 'publish',
                'channels' => array($this->_key),
                'data' => array('-', $packet,
                    array(
                        'rooms' => $this->_rooms,
                        'flags' => $this->_flags
                    )
                )
            )
        );

        $buffer = pack('N', strlen($buffer) + 4) . $buffer;

        fwrite($this->_client, $buffer);

        $this->_rooms = array();
        $this->_flags = array();
        return $this;
    }
}