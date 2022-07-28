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

use \localzet\SocketIO\Event\Emitter;
use \localzet\SocketIO\Debug;

class Transport extends Emitter
{
    public $readyState = 'opening';
    public $req = null;
    public $res = null;

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

    public function close($fn = null)
    {
        $this->readyState = 'closing';
        $fn = $fn ? $fn : array($this, 'noop');
        $this->doClose($fn);
    }

    public function onError($msg, $desc = '')
    {
        if ($this->listeners('error')) {
            $err = array(
                'type' => 'TransportError',
                'description' => $desc,
            );
            $this->emit('error', $err);
        } else {
            echo ("ignored transport error $msg $desc\n");
        }
    }

    public function onPacket($packet)
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

    public function destroy()
    {
        $this->req = $this->res = null;
        $this->readyState = 'closed';
        $this->removeAllListeners();
        $this->shouldClose = null;
    }
}
