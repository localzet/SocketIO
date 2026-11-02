# Localzet SocketIO

[English documentation](README.md)

Реализация Socket.IO-сервера для Localzet Server.

## Состояние и совместимость

Адаптер по умолчанию хранит состояние в памяти и работает без Tunnel. Для межпроцессной доставки нужен необязательный Localzet Tunnel ChannelAdapter; опубликованные выпуски Tunnel 2.x рассчитаны на Server 4.x, поэтому для Server 7 требуется совместимый выпуск Tunnel. Глобальный класс Emitter подключается явной classmap для совместимости. Совместимость с современными клиентами Socket.IO, ограничения протокола и межпроцессная доставка требуют интеграционных проверок до эксплуатации.

Эта версия рассчитана на Localzet Server 7.x. Для Server 4.x используйте совместимый выпуск SocketIO.

## Зависимости

- `php`: `>=8.1`
- `localzet/server`: `^7.0`
- `localzet/tunnel`: необязателен; нужен только для межпроцессного `ChannelAdapter`, совместимый выпуск для Server 7 пока не опубликован.

## Установка

```sh
composer config repositories.localzet-socketio vcs https://github.com/localzet/SocketIO
composer require localzet/socketio:dev-main
```

Пакет пока не опубликован в Packagist; указанный VCS source устанавливает текущую ветку Server 7.

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
