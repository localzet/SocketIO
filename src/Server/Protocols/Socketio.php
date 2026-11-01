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

declare(strict_types=1);

namespace localzet\Server\Protocols;

use localzet\Server\Connection\ConnectionInterface;
use localzet\Server\Protocols\ProtocolInterface;
use localzet\SocketIO\Engine\Protocols\SocketIO as EngineProtocol;

final class Socketio implements ProtocolInterface
{
    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        return EngineProtocol::input($buffer, $connection);
    }

    public static function decode(string $buffer, ConnectionInterface $connection): mixed
    {
        return EngineProtocol::decode($buffer, $connection);
    }

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        return EngineProtocol::encode((string)$data, $connection);
    }
}
