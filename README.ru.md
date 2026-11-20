# Triangle Session

Triangle Session подключает HTTP-сессии Localzet Server к приложениям на Triangle Engine. В пакет входят обработчики для файлов, Redis, Redis Cluster и MongoDB, а также функция `session()` для доступа к сессии текущего запроса.

## Требования

- PHP 8.1 или новее
- `triangle/engine` версии 9.x, которая требует Localzet Server 7.1 или новее

## Установка

```sh
composer require triangle/session
```

Engine 9 запускает `Triangle\Session\Bootstrap` вместе со встроенными компонентами. Это обновление совместимости предназначено для следующей основной версии: Engine 8.1 устанавливает Server 4, в котором нет используемого пакетом API сессий Server 7. Если приложение не публикует конфигурацию пакета автоматически, вызовите `Triangle\Session\Install::install()` в контексте установки приложения. По умолчанию используется файловое хранилище в каталоге `runtime_path('sessions')`.

## Работа с сессией

Функция работает с сессией текущего запроса:

```php
session(['user_id' => 42]);

$userId = session('user_id');
$theme = session('preferences.theme', 'light');
```

Вызов без ключа возвращает объект сессии, строковый ключ читает значение, массив записывает несколько значений. Хранилищем и временем жизни запроса управляет Server. Сохраняйте скаляры и массивы: объекты из сохранённого payload не создаются при чтении.

## Хранилища

| Драйвер | Дополнительное требование | Ключ конфигурации |
| --- | --- | --- |
| `file` | Доступный для записи каталог runtime | `session.config.file` |
| `redis` | PHP-расширение Redis | `session.config.redis` |
| `redis_cluster` | PHP-расширение Redis и настроенный Redis Cluster | `session.config.redis_cluster` |
| `mongo` | `mongodb/mongodb` и PHP-расширение MongoDB | `session.config.mongo` |

Укажите драйвер через `SESSION_TYPE`, а параметры подключения задайте в окружении или `config/session.php`. Для HTTPS включите `SESSION_COOKIE_SECURE=true`. Домен cookie и SameSite выберите с учётом схемы размещения приложения.

## Проверки разработки

```sh
composer validate --strict --no-check-publish
composer test
composer audit --locked
```

Тест совместимости проверяет загрузку Engine bootstrap, контракты обработчиков Server, файловый цикл чтения и записи и безопасную обработку сериализованных значений. MongoDB и Redis не запускаются: сетевые сервисы для теста не требуются.

Copyright © 2016-2026 [Localzet Group](https://www.localzet.com/). All rights reserved.
