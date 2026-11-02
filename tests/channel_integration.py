#!/usr/bin/env python3
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

import os
from pathlib import Path
import signal
import subprocess
import tempfile
import time
import urllib.parse
import urllib.error

from loopback_integration import decode_payload, free_port, request

ROOT = Path(__file__).resolve().parents[1]


def start_process(script, port, runtime, *args):
    env = {
        **os.environ,
        'LOCALZET_PID_FILE': str(runtime / f'{port}.pid'),
        'LOCALZET_LOG_FILE': str(runtime / f'{port}.log'),
    }
    output = open(runtime / f'{port}.stdout.log', 'w')
    process = subprocess.Popen(
        ['php', '-d', 'disable_functions=pcntl_fork', script, 'start', str(port), *map(str, args)],
        cwd=ROOT,
        env=env,
        stdout=output,
        stderr=subprocess.STDOUT,
        start_new_session=True,
    )
    return process, output


def wait_until_ready(process, port, runtime):
    query = {'EIO': '3', 'transport': 'polling', 'b64': '1', 't': 'channel-integration'}
    endpoint = f'http://127.0.0.1:{port}/socket.io/?{urllib.parse.urlencode(query)}'
    deadline = time.monotonic() + 10
    while time.monotonic() < deadline:
        if process.poll() is not None:
            raise RuntimeError(f'Socket.IO process exited with status {process.returncode}')
        try:
            status, payload = request(endpoint)
            packets = decode_payload(payload)
            if status == 200 and packets and packets[0].startswith('0'):
                opened = __import__('json').loads(packets[0][1:])
                sid = opened['sid']
                sid_query = {**query, 'sid': sid}
                sid_endpoint = f'http://127.0.0.1:{port}/socket.io/?{urllib.parse.urlencode(sid_query)}'
                status, payload = request(sid_endpoint)
                if status != 200 or decode_payload(payload) != ['40']:
                    raise RuntimeError(f'Socket.IO namespace connect failed: {payload!r}')
                return sid_endpoint
        except (urllib.error.URLError, KeyError, ValueError):
            time.sleep(0.05)
    log = runtime / f'{port}.stdout.log'
    raise RuntimeError(f'Socket.IO process did not become ready: {log.read_text()}')


def stop_process(process, output):
    if process.poll() is None:
        os.killpg(process.pid, signal.SIGINT)
        try:
            process.wait(timeout=5)
        except subprocess.TimeoutExpired:
            os.killpg(process.pid, signal.SIGKILL)
            process.wait(timeout=5)
    output.close()


def main():
    broker_port, first_port, second_port = free_port(), free_port(), free_port()
    processes = []
    with tempfile.TemporaryDirectory(prefix='socketio-channel-test-') as folder:
        runtime = Path(folder)
        try:
            broker = start_process('tests/tunnel_server.php', broker_port, runtime)
            processes.append(broker)

            deadline = time.monotonic() + 8
            while time.monotonic() < deadline:
                if broker[0].poll() is not None:
                    raise RuntimeError(f'Tunnel broker exited with status {broker[0].returncode}')
                try:
                    import socket
                    with socket.create_connection(('127.0.0.1', broker_port), timeout=0.2):
                        break
                except OSError:
                    time.sleep(0.05)
            else:
                raise RuntimeError('Tunnel broker did not start')

            first = start_process('tests/integration_server.php', first_port, runtime, 'channel', broker_port)
            second = start_process('tests/integration_server.php', second_port, runtime, 'channel', broker_port)
            processes.extend([first, second])
            first_endpoint = wait_until_ready(first[0], first_port, runtime)
            second_endpoint = wait_until_ready(second[0], second_port, runtime)

            time.sleep(0.5)
            event = '42["crossprocess","payload"]'
            status, body = request(first_endpoint, f'{len(event)}:{event}'.encode())
            if status != 200 or body != 'ok':
                raise RuntimeError(f'Cross-process broadcast input failed: {status} {body!r}')

            status, payload = request(second_endpoint, timeout=15)
            if status != 200 or decode_payload(payload) != ['42["crossprocess-result","payload"]']:
                raise RuntimeError(f'Cross-process broadcast was not delivered: {status} {payload!r}')
            print('Socket.IO broadcast delivery across two server processes via Tunnel passed.')
        except Exception:
            for log in runtime.glob('*.log'):
                lines = [line for line in log.read_text().splitlines() if any(token in line for token in ('ChannelAdapter', 'Tunnel client', 'Error', 'Fatal', 'onMessage[socket.io'))]
                if lines:
                    print(f'--- {log.name} ---\n' + '\n'.join(lines))
            raise
        finally:
            for process, output in reversed(processes):
                stop_process(process, output)


if __name__ == '__main__':
    main()
