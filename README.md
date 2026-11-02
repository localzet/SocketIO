# Localzet SocketIO

[Русская документация](README.ru.md)

A Socket.IO server implementation for Localzet Server.

## Status and compatibility

The development branch targets Localzet Server 7. The default in-memory adapter works without Tunnel. Cross-process broadcasts use the optional Tunnel `ChannelAdapter`, compatible with Tunnel 3.x. The global `Emitter` class is included through an explicit Composer classmap. Integration tests cover Engine.IO polling, WebSocket handshakes, namespace connections, event echo and a broadcast across two Socket.IO processes through a live Tunnel broker.

This is a breaking release line for Server 7. Applications on Server 4 must stay on a compatible SocketIO release.

## Dependencies

- `php`: `>=8.1`
- `localzet/server`: `^7.0`
- `localzet/tunnel`: optional at runtime; required for cross-process `ChannelAdapter`. Version `^3.0` targets Server 7.

## Installation

```sh
composer config repositories.localzet-socketio vcs https://github.com/localzet/SocketIO
composer require localzet/socketio:dev-main
```

The package is not published on Packagist yet; this VCS source installs the current Server 7 branch.

## Development checks

```sh
composer validate --strict
composer install
composer dump-autoload --optimize --strict-psr
composer lint
composer test
composer test:integration
composer audit --abandoned=report
```

Installation, lint and autoload checks do not establish end-to-end behavior or production readiness.

The cross-process test starts one Tunnel broker and two independent Socket.IO server processes, connects a client to each server, and verifies that a broadcast from the first reaches the second. It does not cover broker failover, persistence, authentication or delivery during network interruption.

[Historical usage notes](docs/legacy-readme.md) need verification against the current API.

## Author and license

Ivan Zorin (`localzet`), <creator@localzet.com>, https://www.localzet.com.
Source: https://github.com/localzet/SocketIO. AGPL-3.0-or-later; [LICENSE](LICENSE). Original copyright and third-party licenses remain applicable.

[Authors](.github/AUTHORS.md) · [Contributing](.github/CONTRIBUTING.md) · [Security](.github/SECURITY.md)
