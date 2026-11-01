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

namespace localzet\SocketIO\Engine;

use localzet\SocketIO\Debug;
use localzet\SocketIO\Event\Emitter;

class Transport extends Emitter
{
    public $readyState = 'opening';
    public $req = null;
    public $res = null;
    public $shouldClose = null;

    public function __construct()
    {
        Debug::debug('Transport __construct no access !!!!');
    }

    public function __destruct()
    {
        Debug::debug('Transport __destruct');
    }

    public function noop()
    {
    }

    public function onRequest($req)
    {
        $this->req = $req;
    }

    public function close(?callable $fn = null): void
    {
        $this->readyState = 'closing';
        $fn = $fn ?: [$this, 'noop'];
        $this->doClose($fn);
    }

    public function onError(string $msg, string $desc = '')
    {
        if ($this->listeners('error')) {
            $err = [
                'type' => 'TransportError',
                'description' => $desc,
            ];
            $this->emit('error', $err);
        } else {
            echo("ignored transport error $msg $desc\n");
        }
    }

    public function onPacket($packet): void
    {
        $this->emit('packet', $packet);
    }

    public function onData($data)
    {
        $this->onPacket(Parser::decodePacket($data));
    }

    public function onClose()
    {
        $this->req = $this->res = null;
        $this->readyState = 'closed';
        $this->emit('close');
        $this->removeAllListeners();
    }

    public function destroy(): void
    {
        $this->req = null;
        $this->res = null;
        $this->readyState = 'closed';
        $this->removeAllListeners();
        $this->shouldClose = null;
    }
}
