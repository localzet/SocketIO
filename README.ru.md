# Localzet SocketIO

[English documentation](README.md)

Реализация Socket.IO-сервера для Localzet Server.

## Состояние и совместимость

Зависимость localzet/channel заброшена; переход на Tunnel требует проверок совместимости. В коде есть глобальный класс Emitter с явной classmap для совместимости. Совместимость с современными клиентами Socket.IO, ограничения протокола и межпроцессная доставка не подтверждаются проверкой синтаксиса.

Это компонент для Server 4.x; совместимость с Server 7.x не установлена.

## Зависимости

- `php`: `>=8.1`
- `localzet/server`: `^4.1`
- `localzet/channel`: `>=1.0.0`

## Установка

```sh
composer require localzet/socketio
```

## Проверки разработки

```sh
composer validate --strict
composer install
composer dump-autoload --optimize --strict-psr
composer lint
composer test
composer audit --abandoned=report
```

Установка, lint и автозагрузка не подтверждают сквозное поведение или готовность к эксплуатации.

[Исторические примеры](docs/legacy-readme.md) нужно сверять с текущим API.

## Автор и лицензия

Ivan Zorin (`localzet`), <creator@localzet.com>, https://www.localzet.com.
Source: https://github.com/localzet/SocketIO. AGPL-3.0-or-later; [LICENSE](LICENSE). Сохраняются исходные уведомления авторов и лицензии сторонних компонентов.

[Authors](.github/AUTHORS.md) · [Contributing](.github/CONTRIBUTING.md) · [Security](.github/SECURITY.md)
