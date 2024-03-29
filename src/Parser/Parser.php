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
