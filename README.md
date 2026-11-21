<p align="center"><a href="https://www.localzet.com" target="_blank">
  <img src="https://static.zorin.space/media/logos/LocalzetGroup.png" width="400">
</a></p>

<p align="center">
  <a href="https://packagist.org/packages/triangle/session">
  <img src="https://img.shields.io/packagist/dt/triangle/session?label=%D0%A1%D0%BA%D0%B0%D1%87%D0%B8%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F" alt="Скачивания">
</a>
  <a href="https://github.com/Triangle-org/Session">
  <img src="https://img.shields.io/github/commit-activity/t/Triangle-org/Session?label=%D0%9A%D0%BE%D0%BC%D0%BC%D0%B8%D1%82%D1%8B" alt="Коммиты">
</a>
  <a href="https://packagist.org/packages/triangle/session">
  <img src="https://img.shields.io/packagist/v/triangle/session?label=%D0%92%D0%B5%D1%80%D1%81%D0%B8%D1%8F" alt="Версия">
</a>
  <a href="https://packagist.org/packages/triangle/session">
  <img src="https://img.shields.io/packagist/dependency-v/triangle/session/php?label=PHP" alt="Версия PHP">
</a>
  <a href="https://github.com/Triangle-org/Session">
  <img src="https://img.shields.io/github/license/Triangle-org/Session?label=%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F" alt="Лицензия">
</a>
</p>

# Triangle Session

Triangle Session connects Engine applications to the HTTP session storage provided by Localzet Server. It includes file, Redis, Redis Cluster and MongoDB handlers and a `session()` helper for the current request.

## Requirements

- PHP 8.1 or later
- `triangle/engine` 9.x, which requires Localzet Server 7.1 or later

## Installation

```sh
composer require triangle/session:^2.0
```

Engine 9 loads `Triangle\Session\Bootstrap` with its built-in components. This compatibility update is for the next major release because Engine 8.1 resolves to Server 4 and does not provide the Server 7 session API used by this package. To copy the package configuration into an application that does not publish package configuration automatically, run `Triangle\Session\Install::install()` in the application installation context. The default storage driver is `file`; its path comes from `runtime_path('sessions')`.

## Using a session

The helper operates on the current request's Server session:

```php
session(['user_id' => 42]);

$userId = session('user_id');
$theme = session('preferences.theme', 'light');
```

The helper returns the current session when called without a key, reads values for string keys, and writes the supplied values when given an array. Session storage is request-scoped by Server; use scalar values and arrays. Objects restored from stored payloads are not instantiated.

## Storage drivers

| Driver | Additional requirement | Configuration key |
| --- | --- | --- |
| `file` | Writable runtime directory | `session.config.file` |
| `redis` | PHP Redis extension | `session.config.redis` |
| `redis_cluster` | PHP Redis extension and a configured Redis Cluster | `session.config.redis_cluster` |
| `mongo` | `mongodb/mongodb` and the PHP MongoDB extension | `session.config.mongo` |

Set `SESSION_TYPE` to the selected driver and provide its connection details through the environment or `config/session.php`. For HTTPS deployments, set `SESSION_COOKIE_SECURE=true`; configure the cookie domain and SameSite policy for the application’s deployment topology.

## Development checks

```sh
composer validate --strict --no-check-publish
composer test
composer audit --locked
```

The compatibility test exercises Engine bootstrap loading, Server session-handler contracts, file-backed round trips, and safe handling of serialized values. MongoDB and Redis network services are optional and are not started by this test.

Copyright © 2016-2026 [Localzet Group](https://www.localzet.com/). All rights reserved.
