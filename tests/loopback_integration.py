#!/usr/bin/env python3
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

import json
import base64
import hashlib
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


def read_http_head(stream):
    data = b''
    while b'\r\n\r\n' not in data:
        chunk = stream.recv(4096)
        if not chunk:
            raise RuntimeError('Socket.IO WebSocket handshake closed early')
        data += chunk
    head, remainder = data.split(b'\r\n\r\n', 1)
    return head.decode(), remainder


def read_ws_frame(stream, buffered=b''):
    data = bytearray(buffered)

    def take(length):
        while len(data) < length:
            chunk = stream.recv(4096)
            if not chunk:
                raise RuntimeError('Socket.IO WebSocket frame closed early')
            data.extend(chunk)
        result = bytes(data[:length])
        del data[:length]
        return result

    header = take(2)
    length = header[1] & 0x7F
    if length == 126:
        length = int.from_bytes(take(2), 'big')
    elif length == 127:
        length = int.from_bytes(take(8), 'big')
    if header[1] & 0x80:
        mask = take(4)
        payload = bytearray(take(length))
        for index in range(length):
            payload[index] ^= mask[index % 4]
    else:
        payload = take(length)
    return header[0] & 0x0F, payload, bytes(data)


def send_ws_text(stream, payload):
    data = payload.encode()
    mask = os.urandom(4)
    length = len(data)
    if length < 126:
        head = bytes([0x81, 0x80 | length])
    elif length < 65536:
        head = bytes([0x81, 0x80 | 126]) + length.to_bytes(2, 'big')
    else:
        raise RuntimeError('Test WebSocket payload is unexpectedly large')
    stream.sendall(head + mask + bytes(value ^ mask[index % 4] for index, value in enumerate(data)))


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

            key = base64.b64encode(os.urandom(16)).decode()
            ws_query = urllib.parse.urlencode({'EIO': '3', 'transport': 'websocket'})
            ws = socket.create_connection(('127.0.0.1', port), timeout=3)
            ws.settimeout(3)
            ws.sendall((
                f'GET /socket.io/?{ws_query} HTTP/1.1\r\n'
                f'Host: 127.0.0.1:{port}\r\n'
                'Upgrade: websocket\r\n'
                'Connection: Upgrade\r\n'
                f'Sec-WebSocket-Key: {key}\r\n'
                'Sec-WebSocket-Version: 13\r\n\r\n'
            ).encode())
            head, remainder = read_http_head(ws)
            accept = base64.b64encode(hashlib.sha1((key + '258EAFA5-E914-47DA-95CA-C5AB0DC85B11').encode()).digest()).decode()
            if '101 Switching Protocols' not in head or f'Sec-WebSocket-Accept: {accept}' not in head:
                raise RuntimeError(f'Invalid Socket.IO WebSocket upgrade response: {head!r}')
            opcode, open_packet, remainder = read_ws_frame(ws, remainder)
            if opcode != 1 or not open_packet.startswith(b'0'):
                raise RuntimeError(f'Invalid Engine.IO WebSocket open packet: {open_packet!r}')
            opcode, connect_packet, remainder = read_ws_frame(ws, remainder)
            if opcode != 1 or connect_packet != b'40':
                raise RuntimeError(f'Invalid Socket.IO WebSocket namespace packet: {connect_packet!r}')
            send_ws_text(ws, '42["echo","ws"]')
            opcode, echo_packet, remainder = read_ws_frame(ws, remainder)
            if opcode != 1 or echo_packet != b'42["echo","ws"]':
                raise RuntimeError(f'Socket.IO WebSocket echo failed: {echo_packet!r}')
            ws.close()

            print('Socket.IO polling and WebSocket handshakes, namespace connection and event echo passed.')
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
