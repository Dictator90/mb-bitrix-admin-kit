# Bulk actions

## Что это

`BulkAction` выполняется над выбранными строками Bitrix Grid.
Bulk actions отображаются в нативной `ACTION_PANEL` и обрабатываются серверным bulk handler.

## Когда использовать

- массовое удаление;
- массовая активация/деактивация;
- массовое изменение статуса;
- экспорт выбранных записей;
- custom-операции по набору ID.

## Базовый пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::delete(),

        BulkAction::make('activate', 'Активировать')
            ->confirm('Активировать выбранные записи?')
            ->allowRunByFilter()
            ->handle(function (\MB\Bitrix\AdminKit\Database\BulkOperationContext $context) {
                // обработка $context->selectedIds / режима run-by-filter
            }),
    ];
}
```

## Как BulkAction попадает в Bitrix Grid

1. Ресурс/IndexPage возвращает `bulkActions()`.
2. `Grid::setBulkActions()` принимает `BulkAction` и `BulkActionDropdown`.
3. `BitrixGridActionPanelAdapter` конвертирует их в `ACTION_PANEL`.
4. Клиентская callback-функция отправляет selected IDs и state.
5. `IndexBulkActionHandler` выполняет action и формирует результат.

## BulkActionDropdown

`BulkActionDropdown` — только UI-контейнер для группировки `BulkAction`.
Он **не выполняет** операцию самостоятельно.
В `items()` должны быть именно `BulkAction`.

## Selected IDs vs all records

| Режим | Что приходит | Как обрабатывать |
|---|---|---|
| selected IDs | Явный список ID | Выполнить только по этим ID |
| current page selected | ID выбранных строк текущей страницы | Выполнить только по ID |
| all records by filter | Флаг all-rows + filter state | Строить query по фильтру, с QueryGuard/лимитами |

`SHOW_SELECT_ALL_RECORDS_CHECKBOX` — отдельный режим “для всех записей по фильтру”, а не обычный выбор чекбоксами.

## Безопасность

Обязательные правила:

- CSRF (`sessid`) для POST;
- permission checks (`PermissionContext`, `canUpdate/canDelete`);
- action-level `canSee/canRun`;
- per-item permission проверки;
- QueryGuard и лимиты `maxBulkRows()`;
- all-records операции только при явном opt-in (`allowRunByFilter()`, `allowRunWithoutFilter()`).

## Результат выполнения

`BulkResult` агрегирует:

- `success/failed/skipped`;
- `errorsById`;
- summary/message.

Текущее поведение UI:

- итоговое сообщение рендерится как notification на index-странице;
- детализация ошибок выводится частично (ограниченный список);
- автоматическое “богатое” отображение всех bulk-ошибок в grid не гарантируется.

> Если нужна расширенная визуализация bulk errors, требуется отдельная задача на runtime-код.

## Практические сценарии

- bulk delete;
- bulk activate/deactivate;
- bulk export selected;
- confirm перед опасной операцией;
- запрет full-table запуска без filter/opt-in.

## Связанные разделы

- [Grid](grid.md)
- [Actions](actions.md)
- [Permissions](user/guides/permissions.md)
- [Performance & diagnostics](user/guides/performance-diagnostics.md)
- [Import/Export](user/guides/import-export.md)
