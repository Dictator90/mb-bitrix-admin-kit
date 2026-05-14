# Upgrade notes and deprecation policy

## Что изменилось в v0.9.0

- Переписан README под быстрый старт нового разработчика.
- Добавлены `docs/quick-start.md`, cookbook, architecture docs и support-packages docs.
- Добавлен реалистичный demo-module.
- Стабилизированы команды разработки: `composer test`, `composer analyse`, `composer cs-fix`.
- Добавлены PHPStan level 6, php-cs-fixer config и GitHub Actions.

## Deprecated

v0.9.0 не удаляет публичные методы и не вводит намеренных breaking rename. Старые алиасы selector-полей и page API сохраняются.

## Что будет удалено в v1.0

Кандидаты на удаление будут объявлены заранее и помечены `@deprecated`. Удаление публичного метода без deprecation запрещено.

## Как переходить на новые методы

- URL строить через `Support\UrlGenerator`, а не ручную конкатенацию.
- Новые CRUD-разделы наследовать от `CrudResource`.
- Для настроек использовать `Pages\OptionsPage`.
- Для произвольных страниц использовать `Pages\CustomPage`/`DashboardPage`.
- Для helper-логики ядра использовать `AdminCollection`, `AdminString`, `AdminCondition`.

## Deprecation policy

- Не удалять публичные методы без deprecation-периода.
- Deprecated methods помечать phpdoc `@deprecated` с версией и заменой.
- В `docs/upgrade.md` указывать замену и пример миграции.
- Backward-compatible alias оставлять до ближайшей major-версии, если нет критической причины удалить раньше.
