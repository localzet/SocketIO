# Localzet SocketIO

[Русская документация](README.ru.md)

A Socket.IO server implementation for Localzet Server.

## Status and compatibility

The localzet/channel dependency is abandoned. Migration to Tunnel requires compatibility tests. This source includes a global Emitter class with an explicit compatibility classmap; modern Socket.IO client compatibility, protocol limits and cross-process delivery are not established by syntax checks.

This is a Server 4.x component; Server 7.x compatibility is not established.

## Dependencies

- `php`: `>=8.1`
- `localzet/server`: `^4.1`
- `localzet/channel`: `>=1.0.0`

## Installation

```sh
composer require localzet/socketio
```

## Development checks

```sh
composer validate --strict
composer install
composer dump-autoload --optimize --strict-psr
composer lint
composer test
composer audit --abandoned=report
```

Installation, lint and autoload checks do not establish end-to-end behavior or production readiness.

[Historical usage notes](docs/legacy-readme.md) need verification against the current API.

## Author and license

Ivan Zorin (`localzet`), <creator@localzet.com>, https://www.localzet.com.
Source: https://github.com/localzet/SocketIO. AGPL-3.0-or-later; [LICENSE](LICENSE). Original copyright and third-party licenses remain applicable.

[Authors](.github/AUTHORS.md) · [Contributing](.github/CONTRIBUTING.md) · [Security](.github/SECURITY.md)
