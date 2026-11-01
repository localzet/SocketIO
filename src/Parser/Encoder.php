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

namespace localzet\SocketIO\Parser;

use localzet\SocketIO\Debug;
use localzet\SocketIO\Event\Emitter;

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

    public function encode($obj): array
    {
        if (Parser::BINARY_EVENT == $obj['type'] || Parser::BINARY_ACK == $obj['type']) {
            echo new Exception("not support BINARY_EVENT BINARY_ACK");
            return [];
        } else {
            $encoding = self::encodeAsString($obj);
            return [$encoding];
        }
    }

    public static function encodeAsString($obj): string
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
            if ($nsp) {
                $str .= ',';
            }
            $str .= json_encode($obj['data']);
        }

        return $str;
    }
}
