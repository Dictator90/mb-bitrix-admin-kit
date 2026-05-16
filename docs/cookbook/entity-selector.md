# Entity Selector Fields

AdminKit provides a layered family of selector fields built on Bitrix's
`ui.entity-selector` and the custom `mb.ui.dialog-selector` extension.

## Class hierarchy

```
Field
└── EntitySelect          — base: Bitrix TagSelector (ui.entity-selector)
    ├── TagSelect         — thin alias for EntitySelect
    └── DialogSelect      — static-items selector (mb.ui.dialog-selector)
        ├── UserSelect        — pre-wired for Bitrix users
        ├── IblockSelect      — pre-wired for iblock list
        ├── IblockElementSelect — pre-wired for iblock elements
        └── IblockSectionSelect — pre-wired for iblock sections
```

All classes inherit the common `Field` fluent API (`required()`, `readonly()`,
`visibleWhen()`, `dependsOn()`, `displayUsing()`, …).

---

## UserSelect

Select one or many Bitrix users. Labels are resolved automatically via
`Bitrix\Main\UserTable`.

```php
use MB\Bitrix\AdminKit\Field\UserSelect;

// Single user (default)
UserSelect::make('Responsible', 'RESPONSIBLE_ID');

// Multiple users
UserSelect::make('Executors', 'EXECUTOR_IDS')->multiple();
```

Values are stored as comma-separated IDs in a single column (e.g. `"1,42,7"`).

---

## IblockSelect

Select an information-block from the iblock module.

```php
use MB\Bitrix\AdminKit\Field\IblockSelect;

IblockSelect::make('Catalog', 'IBLOCK_ID');
```

---

## IblockElementSelect

Select elements from an iblock. Supports `dependsOn()` to filter by iblock.

```php
use MB\Bitrix\AdminKit\Field\IblockElementSelect;

// Static iblock
IblockElementSelect::make('Product', 'PRODUCT_ID')
    ->iblockId(5);

// Dynamic — filter by sibling field IBLOCK_ID
IblockElementSelect::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');  // iblockId() is applied automatically on change
```

---

## IblockSectionSelect

Select sections from an iblock.

```php
use MB\Bitrix\AdminKit\Field\IblockSectionSelect;

IblockSectionSelect::make('Section', 'SECTION_ID')
    ->iblockId(5)
    ->multiple();
```

Register the provider in the module `.settings.php` (`ui.entity-selector`):

```php
[
    'entityId' => 'iblock-section-list',
    'provider' => [
        'moduleId' => 'your.module',
        'className' => \MB\Bitrix\AdminKit\UI\EntitySelector\IblockSectionListProvider::class,
    ],
],
```

Options: `iblockId` (int), `activeFilter` (bool, only `ACTIVE = Y`).

---

## EntitySelect / TagSelect — generic Bitrix entity

Use `EntitySelect` (or its `TagSelect` alias) when the target entity is
registered in Bitrix's entity-selector registry and you want the standard
TagSelector UI.

```php
use MB\Bitrix\AdminKit\Field\EntitySelect;

EntitySelect::make('Department', 'DEPARTMENT_ID')
    ->entityId('department')           // Bitrix entity-selector entity ID
    ->multiple(false);

// Multiple entities in one dialog
EntitySelect::make('Participants', 'PARTICIPANT_IDS')
    ->entity('user')
    ->entity('department')
    ->multiple();
```

**Built-in label resolvers** — for the entity IDs listed below, labels are
resolved automatically without calling `resolveLabels()`:

| Entity ID | Provider |
|-----------|----------|
| `user`, `user-list` | `Bitrix\Main\UserTable` |
| `user-group`, `user-group-list` | `UserGroupListProvider` |
| `iblock`, `iblock-list` | Bitrix iblock |
| `iblock-element`, `iblock-element-list` | `IblockElementListProvider` |
| `iblock-section`, `iblock-section-list` | `IblockSectionListProvider` |
| `iblock-property`, `iblock-property-list` | `IblockPropertyListProvider` |

For other entities, supply a resolver:

```php
EntitySelect::make('Warehouse', 'WAREHOUSE_ID')
    ->entityId('warehouse')
    ->resolveLabels(static fn (array $ids): array =>
        WarehouseTable::getList(['filter' => ['@ID' => $ids], 'select' => ['ID', 'NAME']])
            ->fetchAll()
    );
```

---

## DialogSelect — static item list

`DialogSelect` uses `MB.UI.DialogSelector.DialogSelector` (from the custom
`mb.ui.dialog-selector` Bitrix extension). Use it when you have a finite set
of known items and no server-side search is needed.

```php
use MB\Bitrix\AdminKit\Field\DialogSelect;

// Simple flat list
DialogSelect::make('Country', 'COUNTRY_CODE')
    ->items([
        ['id' => 'ru', 'entityId' => 'mbDialogEntity', 'title' => 'Russia'],
        ['id' => 'de', 'entityId' => 'mbDialogEntity', 'title' => 'Germany'],
    ]);

// Tabbed dialog
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

**Fluent item/tab API:**

```php
DialogSelect::make('Status', 'STATUS_ID')
    ->addTab(['id' => 'active',   'title' => 'Active'])
    ->addTab(['id' => 'archived', 'title' => 'Archived'])
    ->addItem(['id' => '1', 'entityId' => 'mbDialogEntity', 'title' => 'New',    'tabs' => ['active']])
    ->addItem(['id' => '2', 'entityId' => 'mbDialogEntity', 'title' => 'In work','tabs' => ['active']])
    ->addItem(['id' => '3', 'entityId' => 'mbDialogEntity', 'title' => 'Done',   'tabs' => ['archived']]);
```

`DialogSelect` can also combine dynamic entities with static tabs by mixing
`entityId()` / `entity()` calls (renders via `renderFormFieldWithDialogSelector`).

---

## Common API for all selector fields

### Multiple values

```php
UserSelect::make('Authors', 'AUTHOR_IDS')->multiple();
```

Values are stored comma-separated: `"1,42,7"`.

### Readonly

```php
UserSelect::make('Created by', 'CREATED_BY')->readonly();
```

### Placeholder

```php
UserSelect::make('Responsible', 'RESPONSIBLE_ID')
    ->placeholder('Select a user…');
```

### Conditional visibility

```php
UserSelect::make('Approver', 'APPROVER_ID')
    ->visibleWhen('NEEDS_APPROVAL', 'Y');
```

### Custom label resolver

```php
EntitySelect::make('Project', 'PROJECT_ID')
    ->entityId('project')
    ->resolveLabels(static fn (array $ids): array =>
        array_column(ProjectTable::getList(['filter' => ['@ID' => $ids], 'select' => ['ID', 'NAME']])->fetchAll(), 'NAME', 'ID')
    );
```

### dependsOn (reactive re-render)

```php
// When IBLOCK_ID changes → ELEMENT_ID re-renders with new iblockId()
IblockElementSelect::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');

// Custom modifier
Select::make('Subcategory', 'SUB_ID')
    ->dependsOn('CATEGORY_ID', function (Select $field, mixed $val): void {
        $field->options(SubcategoryTable::optionsForCategory((int)$val));
    });
```

### onChange (reactive source)

```php
Select::make('Iblock', 'IBLOCK_ID')
    ->onChange('ELEMENT_ID', fn ($iblockId) => null);   // clear sibling on change
```

---

## Normalization and storage

| Mode | `normalize()` result | Stored in DB |
|------|---------------------|--------------|
| Single | `string\|null` | `"42"` or `""` |
| Multiple | `string[]` | comma-separated via `serializePostValue()`: `"1,42,7"` |

`parseIds()` splits comma-separated strings automatically, so reading back a
stored multi-value string works without extra conversion.
