#!/usr/bin/env python3
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

import json
import os
from pathlib import Path
import signal
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]


def free_port():
    with socket.socket() as listener:
        listener.bind(('127.0.0.1', 0))
        return listener.getsockname()[1]


def request(url, data=None):
    req = urllib.request.Request(url, data=data, headers={'Content-Type': 'text/plain;charset=UTF-8'})
    with urllib.request.urlopen(req, timeout=3) as response:
        return response.status, response.read().decode()


def decode_payload(payload):
    packets = []
    offset = 0
    while offset < len(payload):
        separator = payload.find(':', offset)
        if separator < 0 or not payload[offset:separator].isdigit():
            raise RuntimeError(f'Invalid Engine.IO polling payload: {payload!r}')
        length = int(payload[offset:separator])
        start = separator + 1
        end = start + length
        if length < 1 or end > len(payload):
            raise RuntimeError(f'Invalid Engine.IO polling payload: {payload!r}')
        packets.append(payload[start:end])
        offset = end
    return packets


def main():
    port = free_port()
    query = {'EIO': '3', 'transport': 'polling', 'b64': '1', 't': 'integration'}
    endpoint = f'http://127.0.0.1:{port}/socket.io/?{urllib.parse.urlencode(query)}'
    with tempfile.TemporaryDirectory(prefix='socketio-test-') as runtime:
        env = {
            **os.environ,
            'LOCALZET_PID_FILE': str(Path(runtime, 'server.pid')),
            'LOCALZET_LOG_FILE': str(Path(runtime, 'server.log')),
        }
        log = open(Path(runtime, 'stdout.log'), 'w')
        process = subprocess.Popen(
            ['php', '-d', 'disable_functions=pcntl_fork', 'tests/integration_server.php', 'start', str(port)],
            cwd=ROOT,
            env=env,
            stdout=log,
            stderr=subprocess.STDOUT,
            start_new_session=True,
        )
        try:
            deadline = time.monotonic() + 8
            while time.monotonic() < deadline:
                if process.poll() is not None:
                    raise RuntimeError(f'Socket.IO server exited with status {process.returncode}')
                try:
                    status, payload = request(endpoint)
                    break
                except urllib.error.URLError:
                    time.sleep(0.05)
            else:
                raise RuntimeError('Socket.IO polling handshake timed out')

            packets = decode_payload(payload)
            if status != 200 or len(packets) != 1 or not packets[0].startswith('0'):
                raise RuntimeError(f'Unexpected Engine.IO open response: {status} {payload!r}')
            opened = json.loads(packets[0][1:])
            sid = opened.get('sid')
            if not sid or 'websocket' not in opened.get('upgrades', []):
                raise RuntimeError(f'Engine.IO open packet is incomplete: {opened!r}')

            sid_query = {**query, 'sid': sid}
            session_endpoint = f'http://127.0.0.1:{port}/socket.io/?{urllib.parse.urlencode(sid_query)}'
            try:
                status, payload = request(session_endpoint)
            except Exception:
                log.flush()
                print(Path(runtime, 'stdout.log').read_text(), flush=True)
                raise
            if status != 200 or decode_payload(payload) != ['40']:
                raise RuntimeError(f'Socket.IO namespace connect was not acknowledged: {status} {payload!r}')

            event = '42["echo","ping"]'
            status, ack = request(session_endpoint, f'{len(event)}:{event}'.encode())
            if status != 200 or ack != 'ok':
                raise RuntimeError(f'Socket.IO event POST failed: {status} {ack!r}')

            status, payload = request(session_endpoint)
            if status != 200 or decode_payload(payload) != [event]:
                raise RuntimeError(f'Socket.IO event echo failed: {status} {payload!r}')

            print('Socket.IO Engine.IO polling handshake, namespace connect and event echo passed.')
        finally:
            os.killpg(process.pid, signal.SIGINT)
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL)
                process.wait(timeout=5)
            log.close()


if __name__ == '__main__':
    main()
