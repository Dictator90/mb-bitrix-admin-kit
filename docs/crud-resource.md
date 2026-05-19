# CrudResource

`CrudResource` — рекомендуемая база для новых ORM CRUD-разделов на Bitrix D7. Он расширяет `Resource`, требует `dataManagerClass()` и наследует настройки grid, export и performance из `Resource` без дублирования.

`Resource` остаётся обратно совместимой базой для legacy-ресурсов с прямым наследованием. Для настроек модуля используйте `Pages\OptionsPage`.

## Минимальный resource

```php
final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [ID::make('ID'), Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')->required()];
    }
}
```

## Persistence

Операции create, update, delete и массового update/delete должны идти через `Database\CrudPersister` и возвращать `DbResult`/`BulkResult` для низкоуровневых ошибок ORM. Так сохраняются согласованность form save, lifecycle-хуков, проверок прав и транзакций. (Persistence CSV-импорта будет использовать тот же путь, когда UI импорта снова включат.)

## Query-хуки

Используйте `indexSelect()`, `indexFilter()`, `indexOrder()`, `indexRuntime()`, `modifyIndexParams()`, `afterIndexRows()` и `mapIndexRow()` для настройки запроса списка без изменения общих внутренностей грида.


## Безопасность массовых операций

Массовые действия на `CrudResource`/`DataManagerResource` по умолчанию работают только с выбранными ID. Используйте `allowRunByFilter()` на прямом `BulkAction` или дочернем действии dropdown, чтобы включить нижний чекбокс Bitrix «для всех записей». Когда передан `action_all_rows_<GRID_ID>=Y`, AdminKit использует текущий фильтр грида вместо переданных selected IDs.

Операции по фильтру с пустым фильтром — это операции по всей таблице; они требуют явного opt-in `allowRunWithoutFilter()`. `QueryGuard` также проверяет `maxBulkRows()` перед материализацией ID; задайте `maxBulkRows(): int` на resource, чтобы снизить или поднять лимит по умолчанию `5000`.
