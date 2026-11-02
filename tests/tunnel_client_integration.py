#!/usr/bin/env python3
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

import os
from pathlib import Path
import signal
import socket
import subprocess
import tempfile
import time

from loopback_integration import free_port

ROOT = Path(__file__).resolve().parents[1]


def start(script, port, runtime, *args):
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


def stop(process, output):
    if process.poll() is None:
        os.killpg(process.pid, signal.SIGINT)
        try:
            process.wait(timeout=5)
        except subprocess.TimeoutExpired:
            os.killpg(process.pid, signal.SIGKILL)
            process.wait(timeout=5)
    output.close()


def main():
    broker_port, listener_port = free_port(), free_port()
    with tempfile.TemporaryDirectory(prefix='tunnel-client-probe-') as folder:
        runtime = Path(folder)
        marker = runtime / 'message.marker'
        broker = start('tests/tunnel_server.php', broker_port, runtime)
        subscriber = None
        try:
            deadline = time.monotonic() + 8
            while time.monotonic() < deadline:
                if broker[0].poll() is not None:
                    raise RuntimeError('Tunnel broker exited before becoming ready.')
                try:
                    with socket.create_connection(('127.0.0.1', broker_port), timeout=0.2):
                        break
                except OSError:
                    time.sleep(0.05)
            else:
                raise RuntimeError('Tunnel broker did not start.')

            subscriber = start('tests/tunnel_client_probe.php', listener_port, runtime, broker_port, marker)
            deadline = time.monotonic() + 8
            trigger = None
            while time.monotonic() < deadline:
                if subscriber[0].poll() is not None:
                    raise RuntimeError('Tunnel client probe exited before listener startup.')
                try:
                    trigger = socket.create_connection(('127.0.0.1', listener_port), timeout=0.2)
                    break
                except OSError:
                    time.sleep(0.05)
            if trigger is None:
                raise RuntimeError(f'Tunnel client probe listener did not start: {(runtime / f"{listener_port}.stdout.log").read_text()}')
            with trigger:
                trigger.settimeout(5)
                trigger.sendall(b'connect')
                try:
                    ready = trigger.recv(16)
                except TimeoutError as error:
                    stdout = runtime / f'{listener_port}.stdout.log'
                    client_log = runtime / f'{listener_port}.log'
                    loop_error = marker.with_suffix(marker.suffix + ".loop-error")
                    raise RuntimeError(f'Tunnel client probe did not acknowledge its connection: entered={marker.with_suffix(marker.suffix + ".entered").exists()} status={marker.with_suffix(marker.suffix + ".status").read_text() if marker.with_suffix(marker.suffix + ".status").exists() else "none"} closed={marker.with_suffix(marker.suffix + ".closed").exists()} error={marker.with_suffix(marker.suffix + ".error").read_text() if marker.with_suffix(marker.suffix + ".error").exists() else "none"} loop_error={loop_error.read_text() if loop_error.exists() else "none"} stdout={stdout.read_text()} client_log={client_log.read_text() if client_log.exists() else "none"}') from error
                if ready != b'ready':
                    raise RuntimeError(f'Tunnel client probe returned unexpected response: {ready!r}')
                connected = marker.with_suffix(marker.suffix + '.connected')
                if not connected.exists():
                    raise RuntimeError('Tunnel client connection handshake did not complete.')

            # The connect callback runs before the queued subscribe frame is flushed.
            time.sleep(0.25)
            payload = b'a:3:{s:4:"type";s:7:"publish";s:8:"channels";a:1:{i:0;s:5:"probe";}s:4:"data";s:5:"hello";}'
            with socket.create_connection(('127.0.0.1', broker_port), timeout=2) as publisher:
                publisher.sendall(len(payload).to_bytes(4, 'big') + payload)

            deadline = time.monotonic() + 5
            while time.monotonic() < deadline and not marker.exists():
                if subscriber[0].poll() is not None:
                    raise RuntimeError('Tunnel client process exited before receiving the event.')
                time.sleep(0.05)
            expected = 's:5:"hello";'
            if not marker.exists() or marker.read_text() != expected:
                stdout = runtime / f'{listener_port}.stdout.log'
                client_log = runtime / f'{listener_port}.log'
                broker_log = runtime / f'{broker_port}.log'
                raise RuntimeError(f'Tunnel client callback did not receive the event: {stdout.read_text()}\nclient={client_log.read_text() if client_log.exists() else "no log"}\nconnect={marker.with_suffix(marker.suffix + ".connect").exists()}\nbroker={broker_log.read_text() if broker_log.exists() else "no log"}')
            print('Tunnel Client async subscription callback received a published event.')
        finally:
            if subscriber is not None:
                stop(*subscriber)
            stop(*broker)


if __name__ == '__main__':
    main()
