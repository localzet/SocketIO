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

namespace localzet\SocketIO\Parser;

use \localzet\SocketIO\Parser\Parser;
use \localzet\SocketIO\Event\Emitter;
use \localzet\SocketIO\Debug;

class Encoder extends Emitter
{
    public function __construct()
    {
        Debug::debug('Encoder __construct');
    }

    public function __destruct()
    {
        Debug::debug('Encoder __destruct');
    }


    public function encode($obj)
    {
        if (Parser::BINARY_EVENT == $obj['type'] || Parser::BINARY_ACK == $obj['type']) {
            echo new \Exception("not support BINARY_EVENT BINARY_ACK");
            return array();
        } else {
            $encoding = self::encodeAsString($obj);
            return array($encoding);
        }
    }

    public static function encodeAsString($obj)
    {
        $str = '';
        $nsp = false;

        // first is type
        $str .= $obj['type'];

        // attachments if we have them
        if (Parser::BINARY_EVENT == $obj['type'] || Parser::BINARY_ACK == $obj['type']) {
            $str .= $obj['attachments'];
            $str .= '-';
        }

        // if we have a namespace other than `/`
        // we append it followed by a comma `,`
        if (!empty($obj['nsp']) && '/' !== $obj['nsp']) {
            $nsp = true;
            $str .= $obj['nsp'];
        }

        // immediately followed by the id
        if (isset($obj['id'])) {
            if ($nsp) {
                $str .= ',';
                $nsp = false;
            }
            $str .= $obj['id'];
        }

        // json data
        if (isset($obj['data'])) {
            if ($nsp) $str .= ',';
            $str .= json_encode($obj['data']);
        }

        return $str;
    }
}
