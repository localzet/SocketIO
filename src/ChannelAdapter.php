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

namespace localzet\SocketIO;

use localzet\Tunnel\Client as TunnelClient;

class ChannelAdapter extends DefaultAdapter
{
    protected $_channelId = null;

    public static $ip = '127.0.0.1';

    public static $port = 2206;

    /**
     * @throws Exception
     */
    public function __construct($nsp)
    {
        parent::__construct($nsp);
        $this->_channelId = (function_exists('random_int') ? random_int(1, 10000000) : rand(1, 10000000)) . "-" . (function_exists('posix_getpid') ? posix_getpid() : 1);
        TunnelClient::$onMessage = [$this, 'onTunnelMessage'];
        TunnelClient::connect('frame://' . self::$ip . ':' . self::$port);
        TunnelClient::on('socket.io#/#', [$this, 'onTunnelMessage']);
        Debug::debug('ChannelAdapter __construct');
    }

    public function __destruct()
    {
        Debug::debug('ChannelAdapter __destruct');
    }

    public function add($id, $room)
    {
        $this->sids[$id][$room] = true;
        $this->rooms[$room][$id] = true;
        $channel = "socket.io#/#$room#";
        TunnelClient::subscribe($channel);
    }

    public function del($id, $room)
    {
        unset($this->sids[$id][$room]);
        unset($this->rooms[$room][$id]);
        if (empty($this->rooms[$room])) {
            unset($this->rooms[$room]);
            $channel = "socket.io#/#$room#";
            TunnelClient::unsubscribe($channel);
        }
    }

    public function delAll($id)
    {
        $rooms = isset($this->sids[$id]) ? array_keys($this->sids[$id]) : [];
        if ($rooms) {
            foreach ($rooms as $room) {
                if (isset($this->rooms[$room][$id])) {
                    unset($this->rooms[$room][$id]);
                    $channel = "socket.io#/#$room#";
                    TunnelClient::unsubscribe($channel);
                }
                if (isset($this->rooms[$room]) && empty($this->rooms[$room])) {
                    unset($this->rooms[$room]);
                }
            }
        }
        unset($this->sids[$id]);
    }

    public function onChannelMessage($channel, $msg)
    {
        if ($this->_channelId === array_shift($msg)) {
            return;
        }

        $packet = $msg[0];

        $opts = $msg[1];

        if (!$packet) {
            echo "invalid  channel:$channel packet \n";
            return;
        }

        if (empty($packet['nsp'])) {
            $packet['nsp'] = '/';
        }

        if ($packet['nsp'] != $this->nsp->name) {
            echo "ignore different namespace {$packet['nsp']} != {$this->nsp->name}\n";
            return;
        }

        $this->broadcast($packet, $opts, true);
    }

    public function onTunnelMessage($msg): void
    {
        $this->onChannelMessage('socket.io#/#', $msg);
    }

    public function broadcast($packet, $opts, $remote = false)
    {
        parent::broadcast($packet, $opts);
        if (!$remote) {
            $packet['nsp'] = '/';

            if (!empty($opts['rooms'])) {
                foreach ($opts['rooms'] as $room) {
                    $chn = "socket.io#/#$room#";
                    $msg = [$this->_channelId, $packet, $opts];
                    TunnelClient::publish($chn, $msg);
                }
            } else {
                $chn = "socket.io#/#";
                $msg = [$this->_channelId, $packet, $opts];
                TunnelClient::publish($chn, $msg);
            }
        }
    }
}
