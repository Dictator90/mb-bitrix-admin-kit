# Fields

AdminKit fields are declarative PHP objects that handle rendering, normalization, validation, and conditional behaviour across list, form, and detail pages. All fields extend the abstract `Field` base class and are created with the static `make()` factory.

## Common Field API

All fields support the backward-compatible fluent API below:

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

Rendering and value lifecycle methods are standardized:

- `renderIndex(mixed $value, array $row = []): string` — grid cell HTML.
- `renderForm(mixed $value = null, array $context = []): string` — edit form HTML.
- `renderDetail(mixed $value, array $row = []): string` — detail page HTML.
- `normalize(mixed $value): mixed` — POST normalization.
- `validate(mixed $value): array` and `runValidation(mixed $value, array $data = []): array` — validation errors.

`displayUsing()` is applied to index/detail rendering and is the preferred way to customize lookup labels without creating one-off field classes:

```php
Text::make('Status', 'STATUS')
    ->displayUsing(static fn (mixed $value, array $row): string => '[' . $value . ']');
```

## ORM Selection Control

- `selectable(bool $selectable = true)` — marks whether the field column should be automatically included in the ORM `select` list. Default is `true`.
- `selectColumns(array|string|null $columns)` — explicitly defines which columns should be selected for this field. Useful when a field depends on multiple database columns.

## Normalization rules

The base field no longer joins arrays with commas. Each concrete field owns its own strategy:

- Scalar fields (`Text`, `Textarea`, `Number`, `Switcher`) return scalar values or `null`.
- `Select::multiple()` returns an array.
- `EntitySelect` / `DialogSelect` (and their subclasses) with `multiple()` store values as comma-separated IDs via `serializePostValue()`.
- `File`, `Image`, and UF multiple fields keep multiple values as arrays.

## Text

```php
Text::make('Title', 'TITLE')
    ->required()
    ->placeholder('Title')
    ->maxLength(255);
```

`Text` renders a Bitrix UI textbox, supports required/readonly/placeholder/help/default values, scalar normalization, and grid/detail escaping.

## Textarea

```php
Textarea::make('Description', 'DESCRIPTION')
    ->rows(8)
    ->placeholder('Description');
```

`Textarea` renders a Bitrix UI textarea and keeps scalar normalization.

## Number

```php
Number::make('Sort', 'SORT')
    ->min(0)
    ->max(1000)
    ->step(1);
```

`Number` renders a numeric input, normalizes numeric strings to `int`/`float`, and returns `null` for empty input.

## Select

`Select` supports plain arrays and callables for options. Callables receive context and the field instance.

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

Single select returns the selected scalar value or `null`; multiple select returns an array and never implodes values. `renderIndex()` and `renderDetail()` show option labels.

## Switcher

```php
Switcher::make('Active', 'ACTIVE')
    ->values('Y', 'N');
```

`Switcher` wraps Bitrix `ui.switcher` and keeps checked/unchecked scalar values.

## Entity selector fields

AdminKit ships two rendering backends and a set of pre-configured concrete fields built on top of them:

```
EntitySelect   — Bitrix ui.entity-selector / BX.UI.EntitySelector.TagSelector
    TagSelect  — thin alias for EntitySelect
    DialogSelect — MB.AdminKit.DialogSelector (mb.admin.kit; static item lists)
        UserSelect          — pre-wired for Bitrix users
        IblockSelect        — pre-wired for iblocks
        IblockElementSelect — pre-wired for iblock elements; supports dependsOn()
        IblockSectionSelect — pre-wired for iblock sections
```

All selector fields share the same `Field` base API and normalize values the same way:

- **Single mode**: one ID string or `null`.
- **Multiple mode**: comma-separated IDs stored in a single column (`"1,42,7"`); `parseIds()` splits them back automatically.

### UserSelect

```php
UserSelect::make('Responsible', 'RESPONSIBLE_ID');          // single (default)
UserSelect::make('Executors', 'EXECUTOR_IDS')->multiple();  // multi
```

Labels are resolved automatically from `Bitrix\Main\UserTable` (name/last name or login).

### IblockSelect

```php
IblockSelect::make('Catalog', 'IBLOCK_ID');
```

### IblockElementSelect

```php
IblockElementSelect::make('Product', 'PRODUCT_ID')->iblockId(5);

// Dynamic — re-renders when IBLOCK_ID changes
IblockElementSelect::make('Element', 'ELEMENT_ID')->dependsOn('IBLOCK_ID');
```

### IblockSectionSelect

```php
IblockSectionSelect::make('Section', 'SECTION_ID')->iblockId(5)->multiple();
```

### EntitySelect / TagSelect — generic Bitrix entity selector

```php
EntitySelect::make('Department', 'DEPARTMENT_ID')->entityId('department');

// Multiple Bitrix entities in one dialog
EntitySelect::make('Participant', 'PARTICIPANT_IDS')
    ->entity('user')
    ->entity('department')
    ->multiple();
```

Built-in label resolution (no `resolveLabels()` call needed): `user`, `user-list`,
`user-group`, `iblock`, `iblock-list`, `iblock-element`, `iblock-property`.

Custom resolver for other entities:

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

### DialogSelect — static item list

`DialogSelect` renders with `MB.AdminKit.DialogSelector` (requires the `mb.admin.kit`
Bitrix extension from `mb.core`). Use it for a known, finite set of items:

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

Or with the lower-level fluent API:

```php
DialogSelect::make('Status', 'STATUS')
    ->addTab(['id' => 'open',   'title' => 'Open'])
    ->addTab(['id' => 'closed', 'title' => 'Closed'])
    ->addItem(['id' => 'new',  'entityId' => 'mbDialogEntity', 'title' => 'New',  'tabs' => ['open']])
    ->addItem(['id' => 'done', 'entityId' => 'mbDialogEntity', 'title' => 'Done', 'tabs' => ['closed']]);
```

## UfField

`UfField` adapts Bitrix user fields and does not replace Bitrix UF internals.

```php
UfField::make('Custom value', 'UF_CUSTOM')
    ->entityId('HLBLOCK_1');
```

The adapter reads Bitrix UF metadata when `USER_FIELD_MANAGER` is available and respects:

- `USER_TYPE_ID` for Bitrix-side rendering;
- `MULTIPLE` for array normalization;
- `MANDATORY` for required validation.

You can provide metadata explicitly in tests or non-Bitrix contexts:

```php
UfField::make('Tags', 'UF_TAGS')
    ->metadata([
        'USER_TYPE_ID' => 'string',
        'MULTIPLE' => 'Y',
        'MANDATORY' => 'N',
    ]);
```

## BelongsTo / BelongsToMany

Select a related record directly from a Bitrix ORM `DataManager` table.
These fields do not require a Bitrix entity-selector entity — the options are
loaded at render time via `getList()`.

### BelongsTo — single foreign-key select

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;

BelongsTo::make('Category', 'CATEGORY_ID', CategoryTable::class)
    ->titleColumn('NAME')           // column shown in the dropdown (default: 'NAME')
    ->valueColumn('ID')             // column used as the option value (default: 'ID')
    ->emptyOption('— select —')     // placeholder option (empty string by default)
    ->filter(['ACTIVE' => 'Y'])     // additional ORM filter
    ->orderBy('SORT');              // ORM order (string → ASC, array → ORM order array)
```

### BelongsToMany — multi-select from a table

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;

BelongsToMany::make('Tags', 'TAG_IDS', TagTable::class)
    ->titleColumn('NAME')
    ->filter(['ACTIVE' => 'Y'])
    ->orderBy('NAME');

// Render as a vertical checkbox list instead of a multi-<select>
BelongsToMany::make('Permissions', 'PERMISSION_IDS', PermissionTable::class)
    ->titleColumn('TITLE')
    ->asCheckboxes();
```

Values are stored as comma-separated IDs (`"1,5,12"`).

---

## Conditional display and validation

`visibleWhen` supports a 2-argument shorthand (column + expected value) or the
full 3-argument form (column, operator, value):

```php
Text::make('External URL', 'EXTERNAL_URL')
    ->visibleWhen('TYPE', 'external')          // shorthand — equals 'external'
    ->visibleWhen('TYPE', '=', 'external')     // same, explicit operator
    ->visibleWhen('MODE', 'in', ['a', 'b'])    // in-array check
    ->requiredWhen('TYPE', '=', 'external')
    ->readonlyWhen('LOCKED', '=', 'Y');
```

Conditions can also be closures or `ConditionTree` objects. `requiredWhen()`
participates in validation; `readonlyWhen()` is checked via `isReadOnlyFor($data)`.

## Dependencies

```php
Select::make('Category', 'CATEGORY_ID')
    ->onChange('SUBCATEGORY_ID', static fn ($categoryId): array => SubcategoryTable::options($categoryId));

IblockElementSelect::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');
```

`dependsOn()` marks a field that must be reconfigured from another field's value.
`onChange()` marks the source field that triggers dependent field refreshes (AJAX re-render).

A custom modifier lets you mutate the dependent field freely:

```php
Select::make('Sub', 'SUB_ID')
    ->dependsOn('CAT_ID', function (Select $field, mixed $val, array $data): void {
        $field->options(SubcategoryRepo::optionsFor((int)$val));
    });
```

## Lookup preloading

For related labels, prefer request-level preloading instead of N+1 calls. `RelationResolver` batches IDs and caches rows per request:

```php
$resolver = new RelationResolver();
$rows = $resolver->preload(ProductTable::class, [1, 2, 3], 'ID', ['ID', 'NAME']);

Text::make('Product', 'PRODUCT_ID')
    ->displayUsing(static fn (mixed $value): string => $rows[(string)$value]['NAME'] ?? (string)$value);
```

## Field inventory

| Class | Purpose | Normalization | Grid/detail |
| --- | --- | --- | --- |
| `Text`, `Textarea`, `Email`, `Password`, `Hidden`, `Html`, `Color`, `Preview`, `ID` | Scalar text-like controls and helpers | Scalar or `null` | Escaped or specialized preview |
| `Number` | Numeric input | Empty→`null`; numeric string→`int`/`float` | Numeric grid column |
| `Select` | Single/multiple choice | Single: scalar/null; multiple: array | Option labels |
| `Checkbox`, `Switcher` | Boolean checked/unchecked | Checked or unchecked scalar value | Checkbox/switcher adapter |
| `Date`, `DateTime` | Bitrix calendar inputs | Scalar date string | Date assembler formatting |
| `File`, `Image` | Bitrix file/image IDs | File strategy (delete/upload companion) | File name / image preview |
| `BelongsTo` | Foreign-key select from a DataManager | Single scalar or `null` | Title column value |
| `BelongsToMany` | Multi-select from a DataManager | Comma-separated IDs | Title column values |
| `EntitySelect` / `TagSelect` | Generic Bitrix entity selector (TagSelector UI) | Single ID or comma-separated IDs | Label chips via resolver |
| `DialogSelect` | Static item list selector (mb.admin.kit) | Single ID or comma-separated IDs | Label chips via resolver |
| `UserSelect` | Bitrix user selector | Single ID or comma-separated IDs | User name chips |
| `IblockSelect` | Iblock selector | Single ID | Iblock name chip |
| `IblockElementSelect` | Iblock element selector; auto-reloads on `dependsOn()` | Single ID or comma-separated IDs | Element name chips |
| `IblockSectionSelect` | Iblock section selector | Single ID or comma-separated IDs | Section name chips |
| `UfField` | Bitrix user-field adapter | Bitrix UF POST shape | Scalar/array display |

All classes inherit `required()`, `readonly()`, `multiple()`, `default()`, `help()`,
`placeholder()`, `visibleWhen()`, `requiredWhen()`, `readonlyWhen()`, `dependsOn()`,
`onChange()`, and `displayUsing()` from `Field`.

## Grid editable metadata

- Inline edit metadata comes from `getGridColumnConfig()` and is enabled only when `editable(true)` is set **and** field is not readonly.
- `Select` exposes Bitrix list editor items as `editable[items]` only for inline-editable columns.
- Relation and entity selector fields (`RelationField` descendants, `EntitySelect`, `DialogSelect`, `TagSelect`, `UserSelect`, `Iblock*Select`) are intentionally non-inline-editable in `main.ui.grid`; edit these values through form/sidepanel flows.
