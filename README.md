# Localzet SocketIO

[Русская документация](README.ru.md)

A Socket.IO server implementation for Localzet Server.

## Status and compatibility

Cross-process delivery uses Localzet Tunnel. The included global Emitter class has an explicit compatibility classmap. Modern Socket.IO client compatibility, protocol limits and cross-process delivery require integration testing before production use.

This release targets Localzet Server 7.x. Server 4.x must use a compatible SocketIO release.

## Dependencies

- `php`: `>=8.1`
- `localzet/server`: `^7.0`
- `localzet/tunnel`: `^2.0`

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
