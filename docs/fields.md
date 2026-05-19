# Поля

Поля AdminKit — декларативные PHP-объекты, отвечающие за рендер, нормализацию, валидацию и условное поведение на страницах списка, формы и детального просмотра. Все поля наследуют абстрактный базовый класс `Field` и создаются через статическую фабрику `make()`.

## Общий API полей

Все поля поддерживают обратно совместимый fluent API:

```php
$field = Text::make('Name', 'NAME')
    ->default('Draft')
    ->required()
    ->readonly(false)
    ->multiple(false)
    ->help('Shown near the control')
    ->placeholder('Enter value')
    ->visibleWhen('TYPE', '=', 'public')
    ->requiredWhen('TYPE', '=', 'public')
    ->readonlyWhen('LOCKED', '=', 'Y')
    ->dependsOn('TYPE')
    ->selectable(false)
    ->selectColumns(['NAME_EN', 'NAME_RU'])
    ->displayUsing(static fn (mixed $value, array $row): string => (string)$value);
```

Методы рендера и жизненного цикла значения стандартизированы:

- `renderIndex(mixed $value, array $row = []): string` — HTML ячейки грида.
- `renderForm(mixed $value = null, array $context = []): string` — HTML формы редактирования.
- `renderDetail(mixed $value, array $row = []): string` — HTML страницы детального просмотра.
- `normalize(mixed $value): mixed` — нормализация POST-данных.
- `validate(mixed $value): array` и `runValidation(mixed $value, array $data = []): array` — ошибки валидации.

`displayUsing()` применяется к рендеру index/detail и является предпочтительным способом настройки подписей связанных записей без одноразовых классов полей:

```php
Text::make('Status', 'STATUS')
    ->displayUsing(static fn (mixed $value, array $row): string => '[' . $value . ']');
```

## Управление выборкой ORM

- `selectable(bool $selectable = true)` — указывает, должна ли колонка поля автоматически попадать в ORM-список `select`. По умолчанию `true`.
- `selectColumns(array|string|null $columns)` — явно задаёт, какие колонки выбирать для этого поля. Полезно, когда поле зависит от нескольких колонок БД.

## Правила нормализации

Базовое поле больше не склеивает массивы через запятую. Стратегию определяет каждое конкретное поле:

- Скалярные поля (`Text`, `Textarea`, `Number`, `Switcher`) возвращают скаляр или `null`.
- `Select::multiple()` возвращает массив.
- `EntitySelect` / `DialogSelect` (и их подклассы) в режиме `multiple()` хранят значения как ID через запятую через `serializePostValue()`.
- `File`, `Image` и множественные UF-поля сохраняют несколько значений как массив.

## Text

```php
Text::make('Title', 'TITLE')
    ->required()
    ->placeholder('Title')
    ->maxLength(255);
```

`Text` рендерит Bitrix UI textbox, поддерживает required/readonly/placeholder/help/default, скалярную нормализацию и экранирование в гриде и на detail.

## Textarea

```php
Textarea::make('Description', 'DESCRIPTION')
    ->rows(8)
    ->placeholder('Description');
```

`Textarea` рендерит Bitrix UI textarea и сохраняет скалярную нормализацию.

## Number

```php
Number::make('Sort', 'SORT')
    ->min(0)
    ->max(1000)
    ->step(1);
```

`Number` рендерит числовой input, нормализует числовые строки в `int`/`float` и возвращает `null` для пустого ввода.

## Select

`Select` поддерживает обычные массивы и callable для опций. Callable получает контекст и экземпляр поля.

```php
Select::make('Status', 'STATUS')
    ->options([
        'new' => 'Новый',
        'done' => 'Готово',
    ])
    ->required();

Select::make('Tags', 'TAGS')
    ->multiple()
    ->options(static fn (): array => [
        'sale' => 'Sale',
        'new' => 'New',
    ]);
```

Одиночный select возвращает выбранное скалярное значение или `null`; множественный — массив и никогда не склеивает значения. `renderIndex()` и `renderDetail()` показывают подписи опций.

## Switcher

```php
Switcher::make('Active', 'ACTIVE')
    ->values('Y', 'N');
```

`Switcher` оборачивает Bitrix `ui.switcher` и хранит скалярные значения для включённого и выключенного состояния.

## Поля entity selector

AdminKit поставляет два бэкенда рендера и набор готовых полей поверх них:

```
EntitySelect   — Bitrix ui.entity-selector / BX.UI.EntitySelector.TagSelector
    TagSelect  — тонкий alias для EntitySelect
    DialogSelect — MB.AdminKit.DialogSelector (mb.admin.kit; статические списки)
        UserSelect          — преднастроен для пользователей Bitrix
        IblockSelect        — преднастроен для инфоблоков
        IblockElementSelect — преднастроен для элементов; поддерживает dependsOn()
        IblockSectionSelect — преднастроен для разделов
```

Все selector-поля используют общий API `Field` и нормализуют значения одинаково:

- **Одиночный режим**: одна строка ID или `null`.
- **Множественный режим**: ID через запятую в одной колонке (`"1,42,7"`); `parseIds()` разбирает их автоматически.

### UserSelect

```php
UserSelect::make('Responsible', 'RESPONSIBLE_ID');          // single (default)
UserSelect::make('Executors', 'EXECUTOR_IDS')->multiple();  // multi
```

Подписи подставляются из `Bitrix\Main\UserTable` (имя/фамилия или логин).

### IblockSelect

```php
IblockSelect::make('Catalog', 'IBLOCK_ID');
```

### IblockElementSelect

```php
IblockElementSelect::make('Product', 'PRODUCT_ID')->iblockId(5);

// Динамически — перерисовка при смене IBLOCK_ID
IblockElementSelect::make('Element', 'ELEMENT_ID')->dependsOn('IBLOCK_ID');
```

### IblockSectionSelect

```php
IblockSectionSelect::make('Section', 'SECTION_ID')->iblockId(5)->multiple();
```

### EntitySelect / TagSelect — универсальный Bitrix entity selector

```php
EntitySelect::make('Department', 'DEPARTMENT_ID')->entityId('department');

// Несколько сущностей Bitrix в одном диалоге
EntitySelect::make('Participant', 'PARTICIPANT_IDS')
    ->entity('user')
    ->entity('department')
    ->multiple();
```

Встроенное разрешение подписей (вызов `resolveLabels()` не нужен): `user`, `user-list`,
`user-group`, `iblock`, `iblock-list`, `iblock-element`, `iblock-property`.

Собственный resolver для других сущностей:

```php
EntitySelect::make('Warehouse', 'WAREHOUSE_ID')
    ->entityId('warehouse')
    ->resolveLabels(static fn (array $ids): array =>
        array_column(
            WarehouseTable::getList(['filter' => ['@ID' => $ids], 'select' => ['ID', 'NAME']])->fetchAll(),
            'NAME', 'ID'
        )
    );
```

### DialogSelect — статический список элементов

`DialogSelect` рендерится через `MB.AdminKit.DialogSelector` (нужно расширение Bitrix
`mb.admin.kit` из `mb.core`). Используйте для известного конечного набора элементов:

```php
DialogSelect::make('Role', 'ROLE_ID')
    ->tabsContent([
        'active' => [
            'title' => 'Active roles',
            'items' => [
                ['id' => '1', 'title' => 'Manager'],
                ['id' => '2', 'title' => 'Developer'],
            ],
        ],
    ])
    ->multiple();
```

Или через низкоуровневый fluent API:

```php
DialogSelect::make('Status', 'STATUS')
    ->addTab(['id' => 'open',   'title' => 'Open'])
    ->addTab(['id' => 'closed', 'title' => 'Closed'])
    ->addItem(['id' => 'new',  'entityId' => 'mbDialogEntity', 'title' => 'New',  'tabs' => ['open']])
    ->addItem(['id' => 'done', 'entityId' => 'mbDialogEntity', 'title' => 'Done', 'tabs' => ['closed']]);
```

## UfField

`UfField` адаптирует пользовательские поля Bitrix и не заменяет внутренности UF.

```php
UfField::make('Custom value', 'UF_CUSTOM')
    ->entityId('HLBLOCK_1');
```

Адаптер читает метаданные UF Bitrix, когда доступен `USER_FIELD_MANAGER`, и учитывает:

- `USER_TYPE_ID` для рендера на стороне Bitrix;
- `MULTIPLE` для нормализации массивов;
- `MANDATORY` для обязательной валидации.

Метаданные можно задать явно в тестах или вне Bitrix:

```php
UfField::make('Tags', 'UF_TAGS')
    ->metadata([
        'USER_TYPE_ID' => 'string',
        'MULTIPLE' => 'Y',
        'MANDATORY' => 'N',
    ]);
```

## BelongsTo / BelongsToMany

Выбор связанной записи напрямую из таблицы Bitrix ORM `DataManager`.
Эти поля не требуют entity Bitrix entity-selector — опции загружаются
в момент рендера через `getList()`.

### BelongsTo — одиночный внешний ключ

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;

BelongsTo::make('Category', 'CATEGORY_ID', CategoryTable::class)
    ->titleColumn('NAME')           // колонка в выпадающем списке (по умолчанию: 'NAME')
    ->valueColumn('ID')             // колонка значения опции (по умолчанию: 'ID')
    ->emptyOption('— select —')     // placeholder-опция (по умолчанию пустая строка)
    ->filter(['ACTIVE' => 'Y'])     // дополнительный ORM-фильтр
    ->orderBy('SORT');              // ORM order (строка → ASC, массив → массив order ORM)
```

### BelongsToMany — множественный выбор из таблицы

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;

BelongsToMany::make('Tags', 'TAG_IDS', TagTable::class)
    ->titleColumn('NAME')
    ->filter(['ACTIVE' => 'Y'])
    ->orderBy('NAME');

// Вертикальный список чекбоксов вместо multi-<select>
BelongsToMany::make('Permissions', 'PERMISSION_IDS', PermissionTable::class)
    ->titleColumn('TITLE')
    ->asCheckboxes();
```

Значения хранятся как ID через запятую (`"1,5,12"`).

---

## Условный показ и валидация

`visibleWhen` поддерживает краткую форму из 2 аргументов (колонка + ожидаемое значение) или
полную форму из 3 аргументов (колонка, оператор, значение):

```php
Text::make('External URL', 'EXTERNAL_URL')
    ->visibleWhen('TYPE', 'external')          // краткая форма — равно 'external'
    ->visibleWhen('TYPE', '=', 'external')     // то же, явный оператор
    ->visibleWhen('MODE', 'in', ['a', 'b'])    // проверка вхождения в массив
    ->requiredWhen('TYPE', '=', 'external')
    ->readonlyWhen('LOCKED', '=', 'Y');
```

Условиями могут быть также замыкания или объекты `ConditionTree`. `requiredWhen()`
участвует в валидации; `readonlyWhen()` проверяется через `isReadOnlyFor($data)`.

## Зависимости

```php
Select::make('Category', 'CATEGORY_ID')
    ->onChange('SUBCATEGORY_ID', static fn ($categoryId): array => SubcategoryTable::options($categoryId));

IblockElementSelect::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');
```

`dependsOn()` помечает поле, которое нужно переконфигурировать от значения другого поля.
`onChange()` помечает исходное поле, запускающее обновление зависимых полей (AJAX re-render).

Собственный модификатор позволяет свободно менять зависимое поле:

```php
Select::make('Sub', 'SUB_ID')
    ->dependsOn('CAT_ID', function (Select $field, mixed $val, array $data): void {
        $field->options(SubcategoryRepo::optionsFor((int)$val));
    });
```

## Предзагрузка lookup

Для подписей связей предпочитайте предзагрузку на уровне запроса вместо N+1. `RelationResolver` батчит ID и кэширует строки в рамках запроса:

```php
$resolver = new RelationResolver();
$rows = $resolver->preload(ProductTable::class, [1, 2, 3], 'ID', ['ID', 'NAME']);

Text::make('Product', 'PRODUCT_ID')
    ->displayUsing(static fn (mixed $value): string => $rows[(string)$value]['NAME'] ?? (string)$value);
```

## Справочник полей

| Класс | Назначение | Нормализация | Grid/detail |
| --- | --- | --- | --- |
| `Text`, `Textarea`, `Email`, `Password`, `Hidden`, `Html`, `Color`, `Preview`, `ID` | Скалярные текстовые контролы и вспомогательные | Скаляр или `null` | Экранирование или спец. preview |
| `Number` | Числовой input | Пусто→`null`; числовая строка→`int`/`float` | Числовая колонка грида |
| `Select` | Одиночный/множественный выбор | Single: scalar/null; multiple: array | Подписи опций |
| `Checkbox`, `Switcher` | Логическое вкл/выкл | Скаляр checked/unchecked | Адаптер checkbox/switcher |
| `Date`, `DateTime` | Календарь Bitrix | Скалярная строка даты | Форматирование date assembler |
| `File`, `Image` | ID файла/изображения Bitrix | Стратегия file (delete/upload companion) | Имя файла / preview изображения |
| `BelongsTo` | Внешний ключ из DataManager | Один скаляр или `null` | Значение title-колонки |
| `BelongsToMany` | Множественный выбор из DataManager | ID через запятую | Значения title-колонок |
| `EntitySelect` / `TagSelect` | Универсальный Bitrix entity selector (TagSelector UI) | Один ID или ID через запятую | Чипы подписей через resolver |
| `DialogSelect` | Статический список (mb.admin.kit) | Один ID или ID через запятую | Чипы подписей через resolver |
| `UserSelect` | Селектор пользователя Bitrix | Один ID или ID через запятую | Чипы имени пользователя |
| `IblockSelect` | Селектор инфоблока | Один ID | Чип имени инфоблока |
| `IblockElementSelect` | Селектор элемента; перезагрузка по `dependsOn()` | Один ID или ID через запятую | Чипы имени элемента |
| `IblockSectionSelect` | Селектор раздела | Один ID или ID через запятую | Чипы имени раздела |
| `UfField` | Адаптер пользовательского поля Bitrix | Форма POST UF Bitrix | Скаляр/массив в отображении |

Все классы наследуют от `Field`: `required()`, `readonly()`, `multiple()`, `default()`, `help()`,
`placeholder()`, `visibleWhen()`, `requiredWhen()`, `readonlyWhen()`, `dependsOn()`,
`onChange()` и `displayUsing()`.

## Метаданные inline-редактирования в гриде

- Метаданные inline-редактирования приходят из `getGridColumnConfig()` и включаются только при `editable(true)` **и** если поле не readonly.
- `Select` отдаёт элементы list editor Bitrix как `editable[items]` только для inline-editable колонок.
- Поля связей и entity selector (`RelationField`, `EntitySelect`, `DialogSelect`, `TagSelect`, `UserSelect`, `Iblock*Select`) намеренно не редактируются inline в `main.ui.grid`; меняйте значения через форму/sidepanel.
