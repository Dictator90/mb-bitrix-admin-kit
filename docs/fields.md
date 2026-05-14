# Fields

AdminKit v0.5.0 keeps the existing field layer and makes its public API consistent across form, grid, and detail pages. Existing classes such as `Text`, `Textarea`, `Number`, `Select`, `Switcher`, `EntitySelect`, `UserSelect`, and `IblockElementSelect` remain available; new `*SelectorField` names are additive adapters over Bitrix UI selectors.

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

## Normalization rules

The base field no longer joins arrays with commas. Each concrete field owns its own strategy:

- Scalar fields (`Text`, `Textarea`, `Number`, `Switcher`) return scalar values or `null`.
- `Select::multiple()` returns an array.
- `EntitySelectorField::multiple()` returns an array of IDs.
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

## EntitySelectorField

`EntitySelectorField` is an adapter over Bitrix `ui.entity-selector` and `BX.UI.EntitySelector.TagSelector`; AdminKit does not implement its own selector engine.

```php
EntitySelectorField::make('Entity', 'ENTITY_ID')
    ->entityId('user')
    ->multiple(false);
```

The field:

- loads the Bitrix `ui.entity-selector` extension when Bitrix is present;
- renders a TagSelector container and hidden inputs;
- passes selected items to the Bitrix dialog;
- normalizes single values to one ID and multiple values to an ID array;
- supports `required`, `readonly`, `visibleWhen`, `dependsOn`, and `displayUsing`.

Use `resolveLabels()` to render human-readable grid/detail values:

```php
EntitySelectorField::make('Responsible', 'RESPONSIBLE_ID')
    ->entityId('user')
    ->resolveLabels(static fn (array $ids): array => UserDirectory::names($ids));
```

## UserSelectorField

```php
UserSelectorField::make('Responsible', 'RESPONSIBLE_ID')
    ->multiple(false);
```

`UserSelectorField` is a narrow user adapter over `EntitySelectorField` with Bitrix user entity configuration and optional Bitrix `UserTable` label resolution. Legacy `UserSelect` remains available.

## IblockElementSelectorField

```php
IblockElementSelectorField::make('Product', 'PRODUCT_ID')
    ->iblockId(5)
    ->multiple(false);
```

This field configures the Bitrix entity selector for iblock elements and resolves labels through the Bitrix iblock module when it is available. Legacy `IblockElementSelect` remains available.

## IblockSectionSelectorField

```php
IblockSectionSelectorField::make('Section', 'SECTION_ID')
    ->iblockId(5)
    ->multiple(false);
```

This field configures the Bitrix entity selector for iblock sections and resolves labels through the Bitrix iblock module when it is available.

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

## Conditional display and validation

```php
Text::make('External URL', 'EXTERNAL_URL')
    ->visibleWhen('TYPE', '=', 'external')
    ->requiredWhen('TYPE', '=', 'external')
    ->readonlyWhen('LOCKED', '=', 'Y');
```

Conditions can be short-form field/operator/value triples, closures, or `AdminCondition` trees. `requiredWhen()` participates in validation, and `readonlyWhen()` is checked through `isReadOnlyFor($data)`.

## Dependencies

```php
Select::make('Category', 'CATEGORY_ID')
    ->onChange('SUBCATEGORY_ID', static fn ($categoryId): array => SubcategoryTable::options($categoryId));

IblockElementSelectorField::make('Element', 'ELEMENT_ID')
    ->dependsOn('IBLOCK_ID');
```

`dependsOn()` marks fields that must be reconfigured from another form value; `onChange()` marks source fields that trigger dependent field refreshes.

## Lookup preloading

For related labels, prefer request-level preloading instead of N+1 calls. `RelationResolver` batches IDs and caches rows per request:

```php
$resolver = new RelationResolver();
$rows = $resolver->preload(ProductTable::class, [1, 2, 3], 'ID', ['ID', 'NAME']);

Text::make('Product', 'PRODUCT_ID')
    ->displayUsing(static fn (mixed $value): string => $rows[(string)$value]['NAME'] ?? (string)$value);
```

## Existing field inventory reviewed for v0.5.0

The v0.5.0 review covered the existing `src/Field` classes rather than replacing them:

| Class | Purpose | Value source/normalization | Grid/detail | Multiple/required/readonly/conditions |
| --- | --- | --- | --- | --- |
| `Field` | Base API for render/normalize/validate/display | Resolves explicit value, stored value, then default; scalar fields return scalar/null and do not implode arrays | Escaped preview with `displayUsing()` hook | Common `required`, `readonly`, `visibleWhen`, `requiredWhen`, `readonlyWhen`, `dependsOn` |
| `Text`, `Textarea`, `Email`, `Password`, `Hidden`, `Html`, `Color`, `Preview`, `ID` | Scalar text-like controls and presentation helpers | Scalar POST values | Escaped or specialized preview | Required/readonly where applicable; conditional API inherited |
| `Number` | Numeric input | Empty to `null`, numeric string to `int`/`float` | Numeric grid type | Required/readonly/placeholder inherited |
| `Select` | Single/multiple choice | Single scalar/null; multiple array | Option labels | Multiple, required, callable options, conditions |
| `Checkbox`, `Switcher` | Boolean-like checked/unchecked scalar values | Checked/unchecked value | Checkbox/switcher grid adapters | Required/readonly behavior inherited where form adapter supports it |
| `Date`, `DateTime` | Bitrix calendar text inputs | Scalar date strings | Date assembler formatting | Required and inherited conditions |
| `File`, `Image` | Bitrix file/image IDs | Concrete file strategy, including delete/upload companion fields | File name/image preview | Multiple support is concrete-field responsibility |
| `EntitySelect`, `UserSelect`, `IblockElementSelect`, `IblockSelect` | Legacy selector names | Kept as compatible adapters over the new selector layer where possible | Label resolver chips | Multiple/required/readonly/dependsOn inherited |
| `EntitySelectorField`, `UserSelectorField`, `IblockElementSelectorField`, `IblockSectionSelectorField` | Bitrix UI EntitySelector adapters | Hidden input POST values, preserving arrays for multiple | Label chips via resolver | Single/multiple, required, readonly, visible/dependent conditions |
| `UfField` | Bitrix user-field adapter | Bitrix UF metadata and POST shape | Scalar/array display | Respects UF `MULTIPLE` and `MANDATORY` metadata |
