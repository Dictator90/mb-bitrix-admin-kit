# Политика обратной совместимости

Начиная с `v1.0.0`, публичный API AdminKit считается стабильным. Релизы `v1.x` (minor/patch) не должны ломать существующие модули.

## Что должно оставаться совместимым

- сигнатуры `public`/`protected` методов;
- публичные имена классов и namespaces;
- базовое поведение CRUD-страниц;
- формат `FormData` (`raw`, `normalized`, `validated`, `errors`);
- формат `GridContext`;
- формат `DbResult` и `BulkResult`;
- базовые точки расширения `Resource`/`CrudResource`;
- базовые API `Field`, `Filter`, `Action`.

## Правила deprecation

Удаление публичного API допускается только после:

1. пометки `@deprecated` в phpdoc;
2. описания миграции в документации;
3. отражения в `CHANGELOG.md`.

## Что можно менять в minor-релизах

- добавлять новые опциональные методы с безопасными дефолтами;
- добавлять новые классы полей/фильтров/действий;
- исправлять баги без изменения контракта поведения;
- расширять документацию и примеры.

## Примечания по стабилизации v1.x

- Legacy-алиасы CRUD-страниц сохранены:
  - `MB\Bitrix\AdminKit\Page\IndexPage` -> `Page\Crud\IndexPage`
  - `MB\Bitrix\AdminKit\Page\FormPage` -> `Page\Crud\FormPage`
  - `MB\Bitrix\AdminKit\Page\DetailPage` -> `Page\Crud\DetailPage`
- `Resource` сохраняет совместимое legacy-поведение (`indexPage`, `formPage`, `detailPage`).
- Legacy fallback для DataManager-сценариев сохранён.
- Ветви раннего JSON-ответа стабилизированы для корректного завершения тестов.

## Задокументированное исключение

В рамках UI-рефакторинга были изменены некоторые контракты:

- вместо широких старых контрактов используются узкие `Contracts\Field\*`, `Contracts\UI\*`, `Contracts\Widget\*`;
- `GraphWidget` сохранён как совместимый alias для `ChartWidget`.

## Миграционный путь (кратко)

1. Обновить `use`-импорты на новые namespaces контрактов.
2. Для UI-компонентов использовать `UI\ComponentContract`/`UI\LayoutComponentContract`.
3. Для полей использовать `Field\FieldContract` или более узкие контракты.
4. Для JS-подключений использовать `mb.admin.kit` вместо legacy-расширений.
