<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$listenerPort = (int)($argv[2] ?? 0);
$tunnelPort = (int)($argv[3] ?? 0);
$marker = $argv[4] ?? '';
if ($listenerPort < 1 || $tunnelPort < 1 || $marker === '') {
    throw new InvalidArgumentException('Listener port, Tunnel port and marker path are required.');
}

\localzet\Server::$pidFile = getenv('LOCALZET_PID_FILE') ?: sys_get_temp_dir() . '/localzet-tunnel-client-probe-' . getmypid() . '.pid';
\localzet\Server::$logFile = getenv('LOCALZET_LOG_FILE') ?: sys_get_temp_dir() . '/localzet-tunnel-client-probe-' . getmypid() . '.log';
$server = new \localzet\Server('tcp://127.0.0.1:' . $listenerPort);
$server->count = 1;
$server->onMessage = static function ($connection) use ($marker, $tunnelPort): void {
    file_put_contents($marker . '.entered', 'yes');
    \localzet\Tunnel\Client::$onConnect = static function () use ($marker, $connection): void {
        file_put_contents($marker . '.connected', 'yes');
        $connection->send('ready');
    };
    \localzet\Tunnel\Client::$onClose = static function () use ($marker): void {
        file_put_contents($marker . '.closed', 'yes', FILE_APPEND);
    };
    \localzet\Tunnel\Client::connect('frame://127.0.0.1:' . $tunnelPort);
    $clientConnection = (new ReflectionProperty(\localzet\Tunnel\Client::class, 'connection'))->getValue();
    $clientConnection->onError = static function ($connection, int $code, string $message) use ($marker): void {
        file_put_contents($marker . '.error', "$code:$message");
    };
    \localzet\Tunnel\Client::on('probe', static function (mixed $data) use ($marker): void {
        file_put_contents($marker, serialize($data));
    });
};
\localzet\Server::runAll();
