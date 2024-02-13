<?php

/**
 * @package     SocketIO Engine
 * @link        https://localzet.gitbook.io
 *
 * @author      localzet <creator@localzet.ru>
 *
 * @copyright   Copyright (c) 2018-2020 Zorin Projects
 * @copyright   Copyright (c) 2020-2022 NONA Team
 *
 * @license     https://www.localzet.ru/license GNU GPLv3 License
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
