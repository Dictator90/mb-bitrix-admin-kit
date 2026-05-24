# Grid

## Что это

`mb-bitrix-admin-kit` строит списки на базе нативного Bitrix-компонента `bitrix:main.ui.grid`.
Admin Kit не заменяет grid-движок Bitrix, а подготавливает:

- колонки (`COLUMNS`);
- строки (`ROWS`);
- row actions;
- action panel для bulk actions;
- параметры пагинации и сортировки;
- интеграцию с `main.ui.filter`.

## Когда использовать

Используйте Grid на index-страницах CRUD, когда нужны:

- список записей;
- сортируемые колонки;
- inline-редактирование (для полей с `editable()`);
- row actions;
- bulk actions;
- фильтрация через `main.ui.filter`;
- пагинация;
- экспорт выбранных записей и/или по фильтру (если разрешено ресурсом).

## Базовый пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\TextFilter;

public function indexFields(): iterable
{
    return [
        ID::make('ID', 'ID')->sortable(),
        Text::make('Название', 'NAME')->sortable()->asEditLink(),
        Text::make('Код', 'CODE')->sortable(),
    ];
}

public function filters(): iterable
{
    return [
        TextFilter::make('Название', 'NAME'),
    ];
}

public function rowActions(): iterable
{
    return [
        RowAction::view(),
        RowAction::edit(),
        RowAction::delete(),
    ];
}

public function bulkActions(): iterable
{
    return [
        BulkAction::delete(),
        BulkAction::make('activate', 'Активировать')
            ->allowRunByFilter()
            ->confirm('Активировать выбранные записи?')
            ->handle(function (\MB\Bitrix\AdminKit\Database\BulkOperationContext $context) {
                // ...
            }),
    ];
}
```

## Как Field становится колонкой

Колонки формируются из `indexFields()` (или `IndexPage::fields()`):

- `GridQueryBuilder` берет поля и строит `select/order/filter/runtime`;
- `BitrixGridAdapter` конвертирует field config в `COLUMNS`;
- `RowAssembler` собирает `ROWS` и actions для каждой строки.

Практические моменты:

- `sortable()` включает сортировку поля;
- `editable()` включает inline-редактирование (если поддерживается типом поля);
- `asEditLink()`/`linkToEdit()` делает значение ссылкой на редактирование;
- computed/selectable поля учитываются при построении select через field API.

## Фильтр над Grid

`filters()` описывает элементы `main.ui.filter`.

Пайплайн:

1. `Filter::getFilterFieldConfig()` формирует конфиг поля фильтра.
2. `GridQueryBuilder` читает filter state (`FilterOptions`/request).
3. `Filter::applyToOrmFilter()` преобразует пользовательское значение в ORM filter.
4. Готовый `filter` передается в `DataManager::getList()`.

## Пагинация и сортировка

- page size берется из `GridOptions` и ограничивается `resource->maxPageSize()`;
- total count включается через `resource->useTotalCount()`;
- кэш count-запроса задается `resource->countCacheTtl()`;
- дефолтная сортировка: `defaultSort()` + `indexOrder()`;
- пользовательская сортировка проходит whitelist через sortable-поля.

## Row actions

Row actions отображаются в меню конкретной строки.
Подробно: [Actions](actions.md).

## Bulk actions / Action panel

Bulk actions рендерятся в нативной нижней панели Bitrix Grid (`ACTION_PANEL`).
`BitrixGridActionPanelAdapter` строит Bitrix panel controls (`BUTTON`/`DROPDOWN`) и JS-callback для запуска.

### Важные различия выбора

| Механизм | Что означает |
|---|---|
| Row checkbox | Выбор конкретной строки |
| Header check-all | Выбор строк на текущей странице |
| `SHOW_SELECT_ALL_RECORDS_CHECKBOX` | Отдельный флаг «для всех записей» (по фильтру) |

`SHOW_SELECT_ALL_RECORDS_CHECKBOX` **не эквивалентен** row/header checkbox.
Backend обязан явно обработать режим «все записи по фильтру», а опасные операции должны оставаться safe-by-default.

## Практические сценарии

- sortable-колонка через `sortable()`;
- колонка-ссылка на edit через `asEditLink()`;
- computed column через field API + `indexSelect()/runtime`;
- фильтр по названию через `TextFilter`;
- отключение total count через `useTotalCount()`;
- добавление bulk action через `bulkActions()`.

## Связанные разделы

- [Fields](fields.md)
- [Filters](user/reference/filters.md)
- [Actions](actions.md)
- [Bulk actions](bulk-actions.md)
- [Resources](resources.md)
- [Performance & diagnostics](user/guides/performance-diagnostics.md)
