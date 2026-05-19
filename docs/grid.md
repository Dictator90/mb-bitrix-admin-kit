# Грид

Grid-слой разделён на сервисы, чтобы UI и ORM-логику не смешивать.

## Зоны ответственности

- `GridQueryBuilder` — единственный источник ORM-параметров (`select`, `filter`, `order`, `runtime`, `limit`, `offset`).
- `GridDataLoader` — формирование `GridContext`, загрузка строк, total count, кеширование count, `QueryGuard`.
- `Grid` — UI-состояние: поля, фильтры, строки, пагинация, action panel.
- Bitrix-адаптеры (`BitrixGridAdapter`, `BitrixFilterAdapter`, `BitrixGridActionPanelAdapter`) — сборка параметров компонентов `main.ui.grid`/`main.ui.filter`.
- `ToolbarRenderer` — интеграция фильтра, create-кнопки и sidepanel-настроек.

## Порядок построения запроса

`GridQueryBuilder` учитывает:

- `indexFields()` и фильтры;
- `defaultSort()`, `defaultFilter()`, `defaultSelect()`;
- `runtimeFields()`;
- `indexSelect()`, `indexFilter()`, `indexOrder()`, `indexRuntime()`;
- `beforeIndexQueryParams()` и `modifyIndexParams()`.

## Интеграция с IndexPage

`Page\Crud\IndexPage` не должен строить ORM-параметры вручную — только делегировать в `GridDataLoader`/`GridQueryBuilder`.

## Загрузка данных

Рекомендуемый сценарий:

1. создать `GridContext`;
2. собрать ORM-параметры через `GridQueryBuilder`;
3. применить guard-ограничения;
4. при необходимости посчитать `total`;
5. получить `DataManager::getList($params)`;
6. передать результат в `Grid::setRawRows()`.

## Валидация сортировки

Сортировка из запроса нормализуется и применяется только к явно sortable-полям.

## Группировка строк

Группировка строится через `Grid\Grouping\IndexGrouping` в рамках обычного `IndexPage`-потока.

- synthetic row IDs: `group:{id}`, `item:{id}`;
- bulk/inline-операции игнорируют `group:*` и нормализуют `item:*`.

## Relation-поля в гриде

`HasMany`/`HasOne` не добавляют JOIN к базовому запросу списка и не должны дублировать строки грида. Загрузка связанных значений выполняется отдельным этапом после выборки базовых строк.


## Панель массовых действий

`BitrixGridActionPanelAdapter` остаётся нативным Bitrix-адаптером для `main.ui.grid` и `Bitrix\Main\Grid\Panel\Types/Actions`. Он не знает о бизнес-id действий: стандартные bulk actions используют JS-обработчик `runBulkAction`, а специальные сценарии задают обработчик через `BulkAction::clientHandler()` (например, export использует `exportSelected`).

AJAX-ответ bulk содержит `success`, `status`, `message`, `summary`, `errors`, `warnings`, `skipped`, `affected` и `successfulIds`; JS показывает ошибки до `reloadTable()`, а PHP flash используется для non-AJAX fallback.

## Матрица совместимости inline-редактирования

- Стабильные inline-типы: `text`, `list`, `date`, `checkbox` (когда поле возвращает соответствующий `getGridColumnType()`).
- Колонка считается inline-редактируемой только если её конфиг `editable` не `false` после проверок readonly.
- Поля readonly (`readonly()` и readonly по умолчанию у relation-полей) всегда отключают метаданные inline-редактирования в итоговом конфиге колонки грида.
- Сложные relation/entity selector поля намеренно исключены из inline-редактирования, чтобы избежать нестабильного поведения runtime грида; используйте ссылки редактирования в sidepanel.
