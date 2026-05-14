# Support packages

AdminKit зависит от трех небольших support-пакетов:

- `mb4it/collections` — единый способ нормализовать iterable/array и выполнять безопасные операции над наборами.
- `mb4it/stringable` — генерация slug, safe key, HTML id, resource id и cache key.
- `mb4it/conditionable` — декларативные условия видимости, readonly, canSee и canRun.

## Почему через адаптеры

В ядре используются только адаптеры AdminKit:

- `Support\AdminCollection`;
- `Support\AdminString`;
- `Support\AdminCondition`.

Так публичный API остается стабильным, даже если внутренняя реализация support-пакета поменяется.

## Почему public API не требует Collection

Разработчик Bitrix-модуля должен возвращать привычные `array`, `iterable`, `callable` и `Closure`. Resource, Field, Filter и Action не заставляют пользователя создавать конкретный Collection-класс, поэтому пакет легче подключить в существующий модуль.

## Почему нельзя использовать глобальные helpers в ядре

Глобальные helpers конфликтуют в Bitrix-проектах: разные модули могут подключить разные версии helper-функций. AdminKit генерирует id, alias, cache keys и нормализует массивы только через адаптеры, чтобы избежать redeclare-конфликтов и скрытых зависимостей.
