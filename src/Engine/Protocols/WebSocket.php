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

namespace localzet\SocketIO\Engine\Protocols;

use localzet\Server\Connection\TcpConnection;
use localzet\SocketIO\Engine\Protocols\Http\Request;
use localzet\SocketIO\Engine\Protocols\Http\Response;
use localzet\SocketIO\Engine\Protocols\WebSocket\RFC6455;

/**
 * WebSocket
 */
class WebSocket
{
    /**
     * 最小包头
     *
     * @var int
     */
    const MIN_HEAD_LEN = 7;

    /**
     * 检查包的完整性
     *
     * @param string $buffer
     */
    public static function input($buffer, $connection)
    {
        if (strlen($buffer) < self::MIN_HEAD_LEN) {
            return 0;
        }
        // flash policy file
        if (0 === strpos($buffer, '<policy')) {
            $policy_xml = '<?xml version="1.0"?><cross-domain-policy><site-control permitted-cross-domain-policies="all"/><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>' . "\0";
            $connection->send($policy_xml, true);
            $connection->consumeRecvBuffer(strlen($buffer));
            return 0;
        }
        // http head
        $pos = strpos($buffer, "\r\n\r\n");
        if (!$pos) {
            if (strlen($buffer) >= TcpConnection::$maxPackageSize) {
                $connection->close("HTTP/1.1 400 bad request\r\n\r\nheader too long");
                return 0;
            }
            return 0;
        }
        $req = new Request($connection, $buffer);
        $res = new Response($connection);
        $connection->consumeRecvBuffer(strlen($buffer));
        return self::dealHandshake($connection, $req, $res);
    }

    /**
     * 处理websocket握手
     *
     * @param TcpConnection $connection
     * @param $req
     * @param $res
     * @return int
     */
    public static function dealHandshake($connection, $req, $res)
    {
        if (isset($req->headers['sec-websocket-key1'])) {
            $res->writeHead(400);
            $res->end("Not support");
            return 0;
        }
        $connection->protocol = 'localzet\SocketIO\Engine\Protocols\WebSocket\RFC6455';
        return RFC6455::dealHandshake($connection, $req, $res);
    }
}
