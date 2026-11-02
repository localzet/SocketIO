# Localzet SocketIO

[English documentation](README.md)

Реализация Socket.IO-сервера для Localzet Server.

## Состояние и совместимость

Ветка разработки рассчитана на Localzet Server 7. Адаптер по умолчанию хранит состояние в памяти и работает без Tunnel. Для межпроцессных рассылок используется необязательный `ChannelAdapter` из Tunnel 3.x. Глобальный класс `Emitter` подключается явной Composer classmap. Интеграционные тесты проверяют Engine.IO polling, WebSocket handshake, подключение namespace, echo события и рассылку между двумя процессами Socket.IO через работающий Tunnel-брокер.

Это несовместимая ветка для Server 7. Приложения на Server 4 должны использовать совместимую версию SocketIO.

## Зависимости

- `php`: `>=8.1`
- `localzet/server`: `^7.0`
- `localzet/tunnel`: необязателен при обычной работе; нужен для межпроцессного `ChannelAdapter`. Версия `^3.0` рассчитана на Server 7.

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
composer test:integration
composer audit --abandoned=report
```

Установка, lint и автозагрузка не подтверждают сквозное поведение или готовность к эксплуатации.

Сквозной тест запускает один Tunnel-брокер и два независимых процесса Socket.IO, подключает клиента к каждому и проверяет доставку рассылки с первого процесса на второй. Проверка не охватывает отказоустойчивость брокера, хранение, аутентификацию и доставку при сетевых сбоях.

[Исторические примеры](docs/legacy-readme.md) нужно сверять с текущим API.

## Автор и лицензия

Ivan Zorin (`localzet`), <creator@localzet.com>, https://www.localzet.com.
Source: https://github.com/localzet/SocketIO. AGPL-3.0-or-later; [LICENSE](LICENSE). Сохраняются исходные уведомления авторов и лицензии сторонних компонентов.

[Authors](.github/AUTHORS.md) · [Contributing](.github/CONTRIBUTING.md) · [Security](.github/SECURITY.md)
