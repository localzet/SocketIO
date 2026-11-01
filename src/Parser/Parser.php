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

class Parser
{
    /**
     * Packet type `connect`.
     *
     * @api public
     */
    const CONNECT = 0;

    /**
     * Packet type `disconnect`.
     *
     * @api public
     */
    const DISCONNECT = 1;

    /**
     * Packet type `event`.
     *
     * @api public
     */
    const EVENT = 2;

    /**
     * Packet type `ack`.
     *
     * @api public
     */
    const ACK = 3;

    /**
     * Packet type `error`.
     *
     * @api public
     */
    const ERROR = 4;

    /**
     * Packet type 'binary event'
     *
     * @api public
     */
    const BINARY_EVENT = 5;

    /**
     * Packet type `binary ack`. For acks with binary arguments.
     *
     * @api public
     */
    const BINARY_ACK = 6;

    public static $types = [
        'CONNECT',
        'DISCONNECT',
        'EVENT',
        'BINARY_EVENT',
        'ACK',
        'BINARY_ACK',
        'ERROR'
    ];
}
