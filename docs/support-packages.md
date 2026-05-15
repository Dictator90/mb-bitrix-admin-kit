# Support packages

AdminKit зависит от четырех небольших support-пакетов:

- `mb4it/collections` — единый способ нормализовать iterable/array и выполнять безопасные операции над наборами.
- `mb4it/stringable` — генерация slug, safe key, HTML id, resource id и cache key.
- `mb4it/conditionable` — декларативные условия видимости, readonly, canSee и canRun.
- `mb4it/filesystem` — файловые операции и `ClassFinder` для discovery потомков `Resource` и standalone-страниц без собственного парсера классов в registry.

## Почему через адаптеры

В ядре используются только адаптеры AdminKit:

- `Support\AdminCollection`;
- `Support\AdminString`;
- `Support\AdminCondition`;
- `Discovery\ClassDiscovery` для изоляции API `Filesystem`/`ClassFinder` от `Manager\AdminKitRegistry`.

Так публичный API остается стабильным, даже если внутренняя реализация support-пакета поменяется.

## Почему public API не требует Collection

Разработчик Bitrix-модуля должен возвращать привычные `array`, `iterable`, `callable` и `Closure`. Resource, Field, Filter и Action не заставляют пользователя создавать конкретный Collection-класс, поэтому пакет легче подключить в существующий модуль.

## Почему нельзя использовать глобальные helpers в ядре

Глобальные helpers конфликтуют в Bitrix-проектах: разные модули могут подключить разные версии helper-функций. AdminKit генерирует id, alias, cache keys и нормализует массивы только через адаптеры, чтобы избежать redeclare-конфликтов и скрытых зависимостей.


## Discovery классов

`AdminKitRegistry::discoverPath()` делегирует поиск классов в `Discovery\ClassDiscovery`. Этот сервис использует `MB\Filesystem\Finder\ClassFinder` из `mb4it/filesystem`, загружает найденные PHP-классы и финально проверяет их через `ReflectionClass::isSubclassOf()`. Благодаря этому registry хранит и сортирует ресурсы/страницы, но не обходит директории и не содержит собственного `token_get_all`-парсера.
