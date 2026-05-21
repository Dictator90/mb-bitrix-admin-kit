# Массовые действия

Массовые действия в v0.4.0 безопасны по умолчанию: выполняются только для явно выбранных ID грида, проверяют Bitrix sessid, обрабатывают ID чанками и проверяют права для каждой строки.

## BulkOperationContext

В обработчики массовых действий передаётся `MB\Bitrix\AdminKit\Database\BulkOperationContext`, который содержит:

- `resource` — текущий ресурс;
- `action` — объект действия или идентификатор действия;
- `selectedIds` — выбранные ID грида;
- `userId` — ID текущего пользователя при наличии;
- `request` — объект запроса Bitrix при наличии;
- `filter` — подготовленный фильтр для будущей поддержки run-by-filter;
- `gridContext` — текущий контекст грида при наличии.

## BulkResult

Обработчики возвращают `MB\Bitrix\AdminKit\Database\BulkResult`. Он сообщает:

- `total` — обработанные строки;
- `successCount` — успешные строки;
- `failedCount` — строки с ошибками;
- `errorsById` — ошибки, сгруппированные по ID строки;
- `skippedIds` — пропущенные ID строк с причинами;
- `message()` — сводка для пользователя: обработано, успешно, пропущено и с ошибкой.

## Массовое обновление

Используйте `BulkAction::update()` для простых обновлений или создавайте `BulkUpdateAction` напрямую:

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate')
            ->label('Активировать')
            ->update(['ACTIVE' => 'Y']),
    ];
}
```

Для каждой выбранной строки действие загружает запись, проверяет `canRun()`, затем `resource->canUpdate()` с `PermissionContext` и обновляет строку через CRUD persistence pipeline. Одна неудачная запись попадает в `BulkResult` и не останавливает остальные.

## Массовое удаление

`BulkAction::delete()` создаёт `MassDeleteAction` с UI по умолчанию для группы danger. `MassDeleteAction` выполняет безопасное массовое удаление:

```php
use MB\Bitrix\AdminKit\Action\MassDeleteAction;

public function bulkActions(): iterable
{
    return [
        MassDeleteAction::make(),
    ];
}
```

Действие проверяет sessid, отклоняет пустой выбор с `Не выбраны элементы`, загружает каждую запись, проверяет `canRun()` и `resource->canDelete()` по записи, вызывает `beforeMassDelete()` / `afterMassDelete()`, удаляет строки через `CrudPersister` и сохраняет все построчные ошибки в `BulkResult`.

## Callback bulk action

`handle()` и совместимый alias `executeUsing()` регистрируют callback. Callback должен возвращать `BulkResult`; это позволяет UI показать частичные ошибки, пропущенные записи и affected count.

Используйте `handle()`, когда операцию нельзя выразить простым update:

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;

BulkAction::make('recalculate')
    ->label('Пересчитать')
    ->handle(function (array $ids, BulkOperationContext $context): BulkResult {
        $result = new BulkResult();

        foreach ($ids as $id) {
            // process row
            $result->addSuccess($id);
        }

        return $result;
    });
```

## UI и группировка Action Panel

Массовые действия можно группировать, сортировать и стилизовать для action panel Bitrix `main.ui.grid`.

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate', 'Активировать')
            ->group('status', 'Статус')
            ->icon('ui-btn-icon-success')
            ->sort(10)
            ->allowRunByFilter()
            ->handle(fn(array $ids, BulkOperationContext $context) => ...),

        BulkAction::make('deactivate', 'Деактивировать')
            ->group('status', 'Статус')
            ->icon('ui-btn-icon-stop')
            ->sort(20)
            ->allowRunByFilter()
            ->handle(fn(array $ids, BulkOperationContext $context) => ...),

        BulkAction::delete()
            ->group('danger', 'Удаление')
            ->sort(100),
    ];
}
```

### Методы UI

- `group(string $id, ?string $label = null, ?int $sort = null)` — ID группы, необязательная подпись и необязательная сортировка группы. Action panel Bitrix группирует элементы визуально.
- `groupSort(int $sort)` — порядок между группами; группы сортируются по `groupSort`, затем по ключу группы.
- `sort(int $sort)` — порядок отображения внутри группы (по умолчанию `100`).
- `icon(string $class)` — CSS-класс иконки Bitrix (например, `ui-btn-icon-remove`, `ui-btn-icon-success`).
- `buttonClass(string $class)` или `class(string $class)` — дополнительные CSS-классы кнопки.
- `title(string $title)` — атрибут `title` кнопки.
- `danger(bool $danger = true)` — помечает действие опасным. В Bitrix UI автоматически добавляется класс `ui-btn-danger`, если другой класс кнопки не задан.
- `confirm(string $message)` — диалог подтверждения Bitrix перед запуском.
- `panelType(string $type)` — тип панели Bitrix (по умолчанию `BUTTON`).
- `panelItem(array|Closure $item)` — сырой массив элемента action panel. Для closure передаётся экземпляр `Grid`.

### Выбрать все записи / режим «для всех»

Если хотя бы у одного прямого bulk action или дочернего действия dropdown включён `allowRunByFilter()`, грид автоматически показывает Bitrix `SHOW_SELECT_ALL_RECORDS_CHECKBOX` — нижний чекбокс action panel для «всех записей». Это не чекбокс заголовка, выбирающий видимые строки.

Когда этот чекбокс отправляет `action_all_rows_<GRID_ID>=Y`, AdminKit считает операцию filter-based, даже если браузер также передал выбранные ID. В этом режиме backend игнорирует выбранные ID и использует текущий фильтр грида.

```php
BulkAction::make('activate', 'Активировать')
    ->allowRunByFilter()
    ->update(['ACTIVE' => 'Y']);
```

Запуск filter-based действия с пустым фильтром означает «все строки» и по умолчанию блокируется. Включайте только для действий, безопасных для всей таблицы:

```php
BulkAction::make('activate_all', 'Активировать все')
    ->allowRunByFilter()
    ->allowRunWithoutFilter()
    ->update(['ACTIVE' => 'Y']);
```

`QueryGuard` считает затронутые строки до материализации ID и блокирует операции выше `maxBulkRows()` (по умолчанию `5000`; добавьте `maxBulkRows(): int` на ресурсе для настройки). Видимость чекбокса можно переопределить на Grid:

```php
$grid->showSelectAllRecordsCheckbox(false);
```

### Dropdown bulk actions

Несколько массовых действий можно сгруппировать в выпадающее меню, чтобы сэкономить место в action panel. `BulkActionDropdown` — UI-контейнер; выполнение делает выбранное дочернее действие.

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;

public function bulkActions(): iterable
{
    return [
        BulkActionDropdown::make('activity', 'Активность')
            ->group('status', 'Статус')
            ->sort(10)
            ->items([
                BulkAction::make('activate', 'Активировать')
                    ->allowRunByFilter()
                    ->update(['ACTIVE' => 'Y']),

                BulkAction::make('deactivate', 'Деактивировать')
                    ->allowRunByFilter()
                    ->update(['ACTIVE' => 'N']),
            ]),

        BulkAction::delete()
            ->group('danger', 'Удаление')
            ->sort(100),
    ];
}
```

- **Только контейнер**: у dropdown нет обработчиков и update data.
- **Placeholder**: Bitrix `Types::DROPDOWN` показывает первый/выбранный элемент как видимую подпись. AdminKit поэтому вставляет placeholder первым; по умолчанию в placeholder идёт подпись dropdown.
- **Placeholder API**: `->placeholder('Выберите действие')` меняет placeholder; `->withoutPlaceholder()` рендерит только исполняемые дочерние элементы.
- **Уникальные ID**: ID дочерних действий должны быть уникальны среди всех bulk actions грида. Placeholder не исполняется и не участвует в проверке ID.
- **Run by filter**: `allowRunByFilter()` задаётся на дочерних действиях. Если любое дочернее (или прямое) действие поддерживает filter run, появится чекбокс «Select all records».
- **Multiple mode**: `multiple(true)` намеренно отклоняется, пока backend не поддержит выполнение нескольких выбранных дочерних действий dropdown.

## Права и условия

`canSee()` управляет отображением действия. `canRun()` проверяется перед запуском и для каждой загруженной строки. Оба метода принимают closures, `ConditionTree` и краткие условия `field/operator/value`. Внутри пакет использует `AdminCondition`.

```php
BulkAction::make('activate')
    ->canRun('ACTIVE', '=', 'N')
    ->update(['ACTIVE' => 'Y']);
```

Строки, не прошедшие `canRun()`, `canUpdate()` или `canDelete()`, попадают в `skippedIds`; они не прерывают операцию.

## Обработка чанками

Ресурс может настроить размер батча:

```php
public function bulkChunkSize(): int
{
    return 50;
}
```

По умолчанию `100`. Массовые действия делят выбранные ID на чанки, чтобы большие явные выборки не обрабатывались одним in-memory батчем.

## Предупреждение о run by filter

Запуск по фильтру может затронуть гораздо больше строк, чем видит пользователь. API подготовлен, но по умолчанию отключён:

```php
BulkAction::make('activate_all_by_filter')
    ->allowRunByFilter();
```

Обрабатываются только явно выбранные ID, пока действие не включит `allowRunByFilter()` и окружение намеренно не передаст отфильтрованные ID.

## Пользовательский client handler

По умолчанию action panel вызывает `kit.GridBulkActions.runBulkAction(config)`. Если действию нужен другой client-side flow, задайте metadata на action, не проверяя конкретный id в адаптере:

```php
BulkAction::make('export_csv', 'Export CSV')
    ->clientHandler('exportSelected');
```

Handler должен быть функцией в namespace расширения `GridBulkActions` `mb.admin.kit`. Небезопасное имя handler автоматически откатывается к `runBulkAction`.

## Результаты для пользователя

`BulkResult::toArray()` отдаёт `success`, `status`, `message`, `summary`, `errors`, `warnings`, `skipped`, `affected` и `successfulIds`. AJAX flow показывает ошибки сразу через `ui.notification`, затем обновляет таблицу. Non-AJAX fallback сохраняет тот же payload во flash session и рендерит `ui.alerts` на следующей загрузке.
