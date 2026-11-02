<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$port = (int)($argv[2] ?? 0);
if ($port < 1 || $port > 65535) {
    throw new InvalidArgumentException('A valid Tunnel test port is required.');
}

\localzet\Server::$pidFile = getenv('LOCALZET_PID_FILE') ?: sys_get_temp_dir() . '/localzet-tunnel-test-' . getmypid() . '.pid';
\localzet\Server::$logFile = getenv('LOCALZET_LOG_FILE') ?: sys_get_temp_dir() . '/localzet-tunnel-test-' . getmypid() . '.log';
$handler = new \localzet\Tunnel\Server(null, true);
$server = new \localzet\Server('frame://127.0.0.1:' . $port);
$server->name = 'Tunnel';
$server->count = 1;
\localzet_bind($server, $handler);
\localzet\Server::runAll();
