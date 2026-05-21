# Обновление и deprecation-политика

## Актуальные изменения ветки

### Import UI временно отключён

- Убраны toolbar/import entrypoints на `IndexPage`.
- `action=import` в index-flow не активен.
- Экспорт остаётся CSV-first.
- Классы `Import\*` сохранены как библиотечный слой.

### Архитектура ресурсов

- Рекомендуемая база для ORM: `DataManagerResource`.
- `Resource` и `CrudResource` сохраняются для совместимости и DSL-сценариев.
- UI-настройка страниц — через `Resource::pages()` и page-классы.

### Грид и безопасность

- ORM-запросы только через `GridQueryBuilder`.
- Загрузка данных только через `GridDataLoader`.
- Bulk-операции ограничиваются `QueryGuard`, проверками прав и лимитами.

### Локализация

- Пользовательские runtime-строки должны идти через `Loc` и `lang/*`.

## Правила миграции

1. Не удалять публичные API без `@deprecated`.
2. Перед удалением документировать замену и шаги миграции.
3. Обновлять `CHANGELOG.md` для каждого пользовательского изменения.

## Политика deprecation

- `@deprecated` должен содержать понятную альтернативу.
- Deprecated-API сохраняется минимум до следующего major-релиза, если нет критической причины удалить раньше.


### Bulk action client handlers

`BitrixGridActionPanelAdapter` больше не выбирает export-поведение по id `export_selected`. Для нестандартного JS flow задавайте `BulkAction::clientHandler('handlerName')`; стандартные действия продолжают использовать `runBulkAction`.

### Поля связей (namespace)

- Используйте только `MB\Bitrix\AdminKit\Field\Relation\{BelongsTo,HasOne,HasMany,BelongsToMany}`.
- Старый namespace `MB\Bitrix\AdminKit\Field\BelongsTo`, deprecated wrappers и `class_alias` не поддерживаются.
- `BelongsToMany` по умолчанию хранит ID в scalar-колонке в формате CSV; ORM ManyToMany включается через `relation()`, `relatedTable()`/`pivotTable()`, `saveUsingOrm()` или `saveUsingManualSync()`.
- `DataManagerResource` всегда использует EntityObject persistence на `FormPage`. Методы `enableEntityObjectForm()` / `usesEntityObjectForm()` удалены; array persistence mode для ORM-форм не поддерживается.

Подробнее: [relations.md](relations.md), [forms.md](forms.md).

