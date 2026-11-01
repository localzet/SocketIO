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

use localzet\Server;
use localzet\SocketIO\Engine\Engine;

class SocketIO
{
    public $worker;
    public $sockets;
    public $nsps = [];
    protected $_nsp = null;
    protected $_socket = null;
    protected $_adapter = null;
    public $engine = null;
    protected $_origins = '*:*';
    protected $_path = null;

    public function __construct($port = null, $opts = [])
    {
        $nsp = $opts['nsp'] ?? '\localzet\SocketIO\Nsp';
        $this->nsp($nsp);

        $socket = $opts['socket'] ?? '\localzet\SocketIO\Socket';
        $this->socket($socket);

        $adapter = $opts['adapter'] ?? '\localzet\SocketIO\DefaultAdapter';
        $this->adapter($adapter);
        if (isset($opts['origins'])) {
            $this->origins($opts['origins']);
        }

        unset($opts['nsp'], $opts['socket'], $opts['adapter'], $opts['origins']);

        $this->sockets = $this->of('/');

        if ($port) {
            $worker = new Server('socketio://0.0.0.0:' . $port, $opts);
            $worker->name = 'SocketIO';

            if (isset($opts['ssl'])) {
                $worker->transport = 'ssl';
            }

            $this->attach($worker);
        }
    }

    public function nsp($v = null)
    {
        if (empty($v)) {
            return $this->_nsp;
        }
        $this->_nsp = $v;
        return $this;
    }

    public function socket($v = null)
    {
        if (empty($v)) {
            return $this->_socket;
        }
        $this->_socket = $v;
        return $this;
    }

    public function adapter($v = null)
    {
        if (empty($v)) {
            return $this->_adapter;
        }
        $this->_adapter = $v;
        foreach ($this->nsps as $nsp) {
            $nsp->initAdapter();
        }
        return $this;
    }

    public function origins($v = null)
    {
        if ($v === null) {
            return $this->_origins;
        }
        $this->_origins = $v;
        if (isset($this->engine)) {
            $this->engine->origins = $this->_origins;
        }
        return $this;
    }

    public function attach($srv, $opts = []): SocketIO
    {
        $engine = new Engine();
        $engine->attach($srv, $opts);

        // Export http server
        $this->worker = $srv;

        // bind to engine events
        $this->bind($engine);

        return $this;
    }

    public function bind($engine): SocketIO
    {
        $this->engine = $engine;
        $this->engine->on('connection', [$this, 'onConnection']);
        $this->engine->origins = $this->_origins;
        return $this;
    }

    public function of($name, $fn = null)
    {
        if ($name[0] !== '/') {
            $name = "/$name";
        }
        if (empty($this->nsps[$name])) {
            $nsp_name = $this->nsp();
            $this->nsps[$name] = new $nsp_name($this, $name);
        }
        if ($fn) {
            $this->nsps[$name]->on('connect', $fn);
        }
        return $this->nsps[$name];
    }

    public function onConnection($engine_socket): SocketIO
    {
        $client = new Client($this, $engine_socket);
        $client->connect('/');
        return $this;
    }

    public function on()
    {
        $args = array_pad(func_get_args(), 2, null);

        if ($args[0] === 'workerStart') {
            $this->worker->onWorkerStart = $args[1];
        } elseif ($args[0] === 'workerStop') {
            $this->worker->onWorkerStop = $args[1];
        } elseif ($args[0] !== null) {
            return call_user_func_array([$this->sockets, 'on'], $args);
        }
    }

    public function in()
    {
        return call_user_func_array([$this->sockets, 'in'], func_get_args());
    }

    public function to()
    {
        return call_user_func_array([$this->sockets, 'to'], func_get_args());
    }

    public function emit()
    {
        return call_user_func_array([$this->sockets, 'emit'], func_get_args());
    }

    public function send()
    {
        return call_user_func_array([$this->sockets, 'send'], func_get_args());
    }

    public function write()
    {
        return call_user_func_array([$this->sockets, 'write'], func_get_args());
    }
}
