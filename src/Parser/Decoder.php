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

class Decoder extends Emitter
{
    public function __construct()
    {
        Debug::debug('Decoder __construct');
    }

    public function __destruct()
    {
        Debug::debug('Decoder __destruct');
    }

    /**
     * @throws Exception
     */
    public function add($obj): void
    {
        if (is_string($obj)) {
            $packet = self::decodeString($obj);
            $this->emit('decoded', $packet);
        }
    }

    /**
     * @throws Exception
     */
    public function decodeString($str): array
    {
        $p = [];
        $i = 0;

        // look up type
        $p['type'] = $str[0];
        if (!isset(Parser::$types[$p['type']])) {
            return self::error();
        }

        // look up attachments if type binary
        if (Parser::BINARY_EVENT == $p['type'] || Parser::BINARY_ACK == $p['type']) {
            $buf = '';
            while ($str[++$i] != '-') {
                $buf .= $str[$i];
                if ($i == strlen($str)) {
                    break;
                }
            }
            if ($buf != intval($buf) || $str[$i] != '-') {
                throw new Exception('Illegal attachments');
            }
            $p['attachments'] = intval($buf);
        }

        // look up namespace (if any)
        if (isset($str[$i + 1]) && '/' === $str[$i + 1]) {
            $p['nsp'] = '';
            while (++$i) {
                if ($i === strlen($str)) {
                    break;
                }
                $c = $str[$i];
                if (',' === $c) {
                    break;
                }
                $p['nsp'] .= $c;
            }
        } else {
            $p['nsp'] = '/';
        }

        // look up id
        if (isset($str[$i + 1])) {
            $next = $str[$i + 1];
            if ('' !== $next && strval((int)$next) === strval($next)) {
                $p['id'] = '';
                while (++$i) {
                    $c = $str[$i];
                    if (null == $c || strval((int)$c) != strval($c)) {
                        --$i;
                        break;
                    }
                    $p['id'] .= $str[$i];
                    if ($i == strlen($str)) {
                        break;
                    }
                }
                $p['id'] = (int)$p['id'];
            }
        }

        // look up json data
        if (isset($str[++$i])) {
            $p['data'] = json_decode(substr($str, $i), true);
        }

        return $p;
    }

    public static function error(): array
    {
        return [
            'type' => Parser::ERROR,
            'data' => 'parser error'
        ];
    }
}
