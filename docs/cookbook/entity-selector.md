# Поля Entity Selector

AdminKit предоставляет многоуровневое семейство полей-селекторов на базе Bitrix
`ui.entity-selector` и расширения пакета `mb.ui.dialog-selector`.

## Иерархия классов

```
Field
└── EntitySelect          — база: Bitrix TagSelector (ui.entity-selector)
    ├── TagSelect         — тонкий алиас для EntitySelect
    └── DialogSelect      — селектор со статическими элементами (mb.ui.dialog-selector)
        ├── UserSelect        — преднастроен для пользователей Bitrix
        ├── IblockSelect      — преднастроен для списка инфоблоков
        ├── IblockElementSelect — преднастроен для элементов инфоблока
        └── IblockSectionSelect — преднастроен для разделов инфоблока
```

Все классы наследуют общий fluent API `Field` (`required()`, `readonly()`,
`visibleWhen()`, `dependsOn()`, `displayUsing()`, …).

---

## UserSelect

Выбор одного или нескольких пользователей Bitrix. Подписи разрешаются автоматически через
`Bitrix\Main\UserTable`.

```php
use MB\Bitrix\AdminKit\Field\UserSelect;

// Один пользователь (по умолчанию)
UserSelect::make('Responsible', 'RESPONSIBLE_ID');

// Несколько пользователей
UserSelect::make('Executors', 'EXECUTOR_IDS')->multiple();
```

Значения хранятся как ID через запятую в одной колонке (например, `"1,42,7"`).

---

## IblockSelect

Выбор информационного блока из модуля iblock.

```php
use MB\Bitrix\AdminKit\Field\IblockSelect;

IblockSelect::make('Catalog', 'IBLOCK_ID');
```

---

## IblockElementSelect

Выбор элементов инфоблока. Поддерживает `dependsOn()` для фильтрации по инфоблоку.

```php
use MB\Bitrix\AdminKit\Field\IblockElementSelect;

// Статический инфоблок
IblockElementSelect::make('Product', 'PRODUCT_ID')
    ->iblockId(5);

// Динамический — фильтр по соседнему полю IBLOCK_ID
IblockElementSelect::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');  // iblockId() применяется автоматически при изменении
```

---

## IblockSectionSelect

Выбор разделов инфоблока.

```php
use MB\Bitrix\AdminKit\Field\IblockSectionSelect;

IblockSectionSelect::make('Section', 'SECTION_ID')
    ->iblockId(5)
    ->multiple();
```

Зарегистрируйте провайдер в `.settings.php` модуля (`ui.entity-selector`):

```php
[
    'entityId' => 'iblock-section-list',
    'provider' => [
        'moduleId' => 'your.module',
        'className' => \MB\Bitrix\AdminKit\UI\EntitySelector\IblockSectionListProvider::class,
    ],
],
```

Опции: `iblockId` (int), `activeFilter` (bool, только `ACTIVE = Y`).

---

## EntitySelect / TagSelect — универсальная сущность Bitrix

Используйте `EntitySelect` (или алиас `TagSelect`), когда целевая сущность
зарегистрирована в реестре entity-selector Bitrix и нужен стандартный
UI TagSelector.

```php
use MB\Bitrix\AdminKit\Field\EntitySelect;

EntitySelect::make('Department', 'DEPARTMENT_ID')
    ->entityId('department')           // ID сущности Bitrix entity-selector
    ->multiple(false);

// Несколько сущностей в одном диалоге
EntitySelect::make('Participants', 'PARTICIPANT_IDS')
    ->entity('user')
    ->entity('department')
    ->multiple();
```

**Встроенные резолверы подписей** — для перечисленных entity ID подписи
разрешаются автоматически без вызова `resolveLabels()`:

| Entity ID | Provider |
|-----------|----------|
| `user`, `user-list` | `Bitrix\Main\UserTable` |
| `user-group`, `user-group-list` | `UserGroupListProvider` |
| `iblock`, `iblock-list` | Bitrix iblock |
| `iblock-element`, `iblock-element-list` | `IblockElementListProvider` |
| `iblock-section`, `iblock-section-list` | `IblockSectionListProvider` |
| `iblock-property`, `iblock-property-list` | `IblockPropertyListProvider` |

Для других сущностей укажите резолвер:

```php
EntitySelect::make('Warehouse', 'WAREHOUSE_ID')
    ->entityId('warehouse')
    ->resolveLabels(static fn (array $ids): array =>
        WarehouseTable::getList(['filter' => ['@ID' => $ids], 'select' => ['ID', 'NAME']])
            ->fetchAll()
    );
```

---

## DialogSelect — статический список элементов

`DialogSelect` использует `MB.AdminKit.DialogSelector` (из расширения `mb.admin.kit`).
Подходит, когда есть конечный набор известных элементов и серверный поиск не нужен.

```php
use MB\Bitrix\AdminKit\Field\DialogSelect;

// Простой плоский список
DialogSelect::make('Country', 'COUNTRY_CODE')
    ->items([
        ['id' => 'ru', 'entityId' => 'mbDialogEntity', 'title' => 'Russia'],
        ['id' => 'de', 'entityId' => 'mbDialogEntity', 'title' => 'Germany'],
    ]);

// Диалог с вкладками
DialogSelect::make('Role', 'ROLE_ID')
    ->tabsContent([
        'managers' => [
            'title' => 'Managers',
            'items' => [
                ['id' => '1', 'title' => 'Account manager'],
                ['id' => '2', 'title' => 'Sales manager', 'subtitle' => 'B2B'],
            ],
        ],
        'support' => [
            'title' => 'Support',
            'items' => [
                ['id' => '3', 'title' => 'L1 Support'],
                ['id' => '4', 'title' => 'L2 Support'],
            ],
        ],
    ])
    ->multiple();
```

**Fluent API элементов/вкладок:**

```php
DialogSelect::make('Status', 'STATUS_ID')
    ->addTab(['id' => 'active',   'title' => 'Active'])
    ->addTab(['id' => 'archived', 'title' => 'Archived'])
    ->addItem(['id' => '1', 'entityId' => 'mbDialogEntity', 'title' => 'New',    'tabs' => ['active']])
    ->addItem(['id' => '2', 'entityId' => 'mbDialogEntity', 'title' => 'In work','tabs' => ['active']])
    ->addItem(['id' => '3', 'entityId' => 'mbDialogEntity', 'title' => 'Done',   'tabs' => ['archived']]);
```

`DialogSelect` может сочетать динамические сущности со статическими вкладками, смешивая
вызовы `entityId()` / `entity()` (рендер через `renderFormFieldWithDialogSelector`).

---

## Общий API для всех полей-селекторов

### Несколько значений

```php
UserSelect::make('Authors', 'AUTHOR_IDS')->multiple();
```

Значения хранятся через запятую: `"1,42,7"`.

### Readonly

```php
UserSelect::make('Created by', 'CREATED_BY')->readonly();
```

### Placeholder

```php
UserSelect::make('Responsible', 'RESPONSIBLE_ID')
    ->placeholder('Select a user…');
```

### Условная видимость

```php
UserSelect::make('Approver', 'APPROVER_ID')
    ->visibleWhen('NEEDS_APPROVAL', 'Y');
```

### Пользовательский резолвер подписей

```php
EntitySelect::make('Project', 'PROJECT_ID')
    ->entityId('project')
    ->resolveLabels(static fn (array $ids): array =>
        array_column(ProjectTable::getList(['filter' => ['@ID' => $ids], 'select' => ['ID', 'NAME']])->fetchAll(), 'NAME', 'ID')
    );
```

### dependsOn (реактивный перерендер)

```php
// При смене IBLOCK_ID → ELEMENT_ID перерендерится с новым iblockId()
IblockElementSelect::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');

// Пользовательский модификатор
Select::make('Subcategory', 'SUB_ID')
    ->dependsOn('CATEGORY_ID', function (Select $field, mixed $val): void {
        $field->options(SubcategoryTable::optionsForCategory((int)$val));
    });
```

### onChange (источник реактивности)

```php
Select::make('Iblock', 'IBLOCK_ID')
    ->onChange('ELEMENT_ID', fn ($iblockId) => null);   // очистить соседнее поле при изменении
```

---

## Нормализация и хранение

| Режим | Результат `normalize()` | В БД |
|------|---------------------|--------------|
| Single | `string\|null` | `"42"` или `""` |
| Multiple | `string[]` | через запятую в `serializePostValue()`: `"1,42,7"` |

`parseIds()` автоматически разбивает строки с ID через запятую, поэтому чтение
сохранённой multi-value строки работает без дополнительного преобразования.
