<?php

/**
 * @package     Localzet Socket.IO
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$port = (int)($argv[2] ?? 0);
if ($port < 1 || $port > 65535) {
    throw new InvalidArgumentException('A valid test port is required.');
}

\localzet\Server::$pidFile = getenv('LOCALZET_PID_FILE') ?: sys_get_temp_dir() . '/localzet-socketio-test-' . getmypid() . '.pid';
\localzet\Server::$logFile = getenv('LOCALZET_LOG_FILE') ?: sys_get_temp_dir() . '/localzet-socketio-test-' . getmypid() . '.log';

$socketIo = new \localzet\SocketIO\SocketIO($port);
$socketIo->on('connection', static function ($socket): void {
    $socket->on('echo', static function (string $message) use ($socket): void {
        $socket->emit('echo', $message);
    });
});
\localzet\Server::runAll();
