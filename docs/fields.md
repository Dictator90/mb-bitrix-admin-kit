# Fields

`MB\Bitrix\AdminKit\Field\Field` — базовая декларация поля для index/form/detail/options, а также для import/export и валидации.

Поле хранит:
- идентичность (`label`, `column`);
- правила видимости/readonly/required;
- форматирование и preview;
- grid metadata (sortable/editable/edit-link);
- normalize/serialize поведение для POST;
- reactive dependencies (`dependsOn`, `onChange`).

## 1) Быстрый пример

```php
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Switcher;

public function formFields(): iterable
{
    return [
        Text::make('Название', 'NAME')
            ->required()
            ->placeholder('Введите название'),

        Switcher::make('Активность', 'ACTIVE')
            ->values('Y', 'N')
            ->default('Y'),
    ];
}

public function indexFields(): iterable
{
    return [
        ID::make('ID'),
        Text::make('Название', 'NAME')->sortable()->asEditLink(),
        Switcher::make('Активность', 'ACTIVE')->values('Y', 'N'),
    ];
}
```

- 1-й аргумент `make()` — label.
- 2-й аргумент — column (ключ данных/ORM поля/option key).
- Если column не указан, он генерируется из label через `AdminString::safeKey()`.

## 2) Создание поля и идентичность

- `make(string $label, ?string $column = null): static`
- `getLabel(): string`
- `getColumn(): string`

`column` используется как ключ значения в форме, POST, row-data и ORM/select/filter слоях.

## 3) Значение поля

- `setValue(mixed $value): static`
- `fill(mixed $value): static` (алиас)
- `getValue(): mixed`
- `resolveValue(mixed $item, array $row = []): mixed`

`resolveValue()` берет значение в таком порядке: `item[column]` (array), `item->get(column)` (object), затем fallback по `row[column]`, `value`, `default`.

## 4) Значение по умолчанию

- `default(mixed $value): static`
- `getDefault(): mixed`

`default` — fallback при resolve, но не «принудительная запись в БД».

## 5) Видимость по страницам

- `hideOn(PageType ...$pageTypes): static`
- `showOn(PageType ...$pageTypes): static`
- `isVisibleOn(PageType $pageType): bool`

Это server-side видимость в контексте типа страницы (index/form/detail/options рендера).

## 6) Условная видимость: `visible()`, `canSee()`, `visibleWhen()`

- `visible(bool|Closure $visible = true, string|array|null $dependsOn = null): static`
- `canSee(bool|Closure $condition = true, string|array|null $dependsOn = null): static` (алиас)
- `visibleWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null, bool $reactive = false): static`

```php
use MB\Bitrix\AdminKit\Field\FieldConditionContext;

Text::make('Комментарий', 'COMMENT')
    ->canSee(fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'));

Text::make('Комментарий', 'COMMENT')
    ->visibleWhen('ACTIVE', '=', 'Y', reactive: true);
```

## 7) Required и validation

- `required(bool|Closure $required = true, string|array|null $dependsOn = null): static`
- `requiredWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null, bool $reactive = false): static`
- `validate(mixed $value): array|static` (closure → добавить validator, scalar → выполнить validation)
- `runValidation(mixed $value, array $data = []): array`

Validation helpers:
- `minLength()`, `maxLength()`, `email()`, `url()`, `numeric()`, `min()`, `max()`, `pattern()`, `in()`.

## 8) Readonly

- `readonly(bool|Closure $readonly = true, string|array|null $dependsOn = null): static`
- `readonlyWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null, bool $reactive = false): static`
- `readonlyOnUpdate(bool $readonly = true): static`
- `readonlyOnCreate(bool $readonly = true): static`
- `isReadOnly(): bool`
- `isReadOnlyFor(array $data = []): bool`

`readonlyOnUpdate()` и `readonlyOnCreate()` используют form-контекст (`_mode`, `_id`, `ID`).

## 9) Универсальная условная логика `when()`

- `when(Closure $condition, Closure $modifier, string|array|null $dependsOn = null): static`

Контекст условий: `FieldConditionContext` (`get/has/is/isNot/in/isCreate/isEdit`).

```php
Text::make('SEO title', 'SEO_TITLE')
    ->when(
        condition: fn (FieldConditionContext $ctx): bool => $ctx->is('SEO_ENABLED', 'Y'),
        modifier: fn (Text $field): Text => $field->required()->help('Заполните SEO title'),
        dependsOn: 'SEO_ENABLED',
    );
```

## 10) Reactivity: `dependsOn()` и `onChange()`

- `dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static`
- `onChange(string $targetColumn, Closure $resolver): static`
- `resolveReactiveDependencies(mixed $value, array $allData = []): array`
- low-level: `hasDependency()`, `getDependsOn()`, `applyDependency()`, `isReactive()`, `getOnChangeCallbacks()`, `getReactiveAttributes()`.

`dependsOn` включает server + frontend реактивный сценарий (через `install/js/mb/admin/kit/src/dependencies.js`).

## 11) Форматирование и preview

- `format(Closure $formatter): static`
- `displayUsing(Closure $callback): static`
- `preview(Closure $preview): static`
- `displayValue(...)`, `previewValue(...)`

`displayUsing()` — самый гибкий путь (value + row + context), `format()` — удобный shorthand для value-only.

## 12) Grid и inline edit

- `sortable(bool $sortable = true): static`
- `editable(bool $editable = true): static`
- `asEditLink(bool $enabled = true): static`
- `linkToEdit(bool $enabled = true): static` (алиас)
- `getGridColumnConfig(): array`
- `getGridColumnType(): string`
- `getFilterType(): ?string`

Важно: computed field автоматически отключает sortable.

## 13) ORM select и computed

- `selectable(bool $selectable = true): static`
- `selectColumns(array|string|null $columns): static`
- `getSelectColumns(): array`
- `computed(Closure $callback): static`
- `isComputed(): bool`
- `computeValue(array $row): mixed`

```php
Text::make('Полное имя', 'FULL_NAME')
    ->computed(fn (array $row): string => trim(($row['LAST_NAME'] ?? '') . ' ' . ($row['NAME'] ?? '')))
    ->selectColumns(['LAST_NAME', 'NAME']);
```

## 14) Export / Import

- `exportable(bool $exportable = true): static`
- `private(bool $private = true): static`
- `importable(bool $importable = true): static`
- `system(bool $system = true): static`

## 15) Help / hint / placeholder

- `hint(string $hint): static`
- `help(?string $text): static`
- `placeholder(?string $text): static`

`help()` в текущем API задает hint-текст (не отдельный блок описания под полем).

## 16) Multiple/normalize/serialization

- `multiple(bool $multiple = true): static`
- `normalize(mixed $value): mixed`
- `serializePostValue(mixed $value): mixed`
- `preserveStoredValueWhenEmpty(): bool`

## 17) Render lifecycle

- `renderIndex(mixed $context, array $row = []): string`
- `renderForm(mixed $context = null, array $data = []): string`
- `renderFormField(mixed $value = null): string` (базовый контракт)
- `renderDetail(mixed $context, array $row = []): string`

`FieldRenderContext` и `FieldRowRenderer`/`FieldRowContext` используются для page-aware рендера (label/errors/wrapper/hint выносится в row renderer).

## 18) Кастомное поле

```php
use MB\Bitrix\AdminKit\Field\Field;

final class ColorField extends Field
{
    public function renderFormField(mixed $value = null): string
    {
        $name = htmlspecialcharsbx($this->getColumn());
        $resolved = htmlspecialcharsbx((string) $this->resolveValue($value));

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="color" class="ui-ctl-element" name="{$name}" value="{$resolved}">
        </div>
        HTML;
    }

    public function getGridColumnType(): string
    {
        return 'color';
    }
}
```

Если нужно использовать вторым аргументом `$formData`, это поддерживается runtime-вызовом `renderForm()` в `Field`, но формальный контракт `renderFormField()` остается с одним параметром.

## 19) Обзор стандартных полей (`src/Field/*`)

- `ID` — ID/инт-идентификатор, обычно readonly/system-like.
- `Text` — базовый input text.
- `Textarea` — многострочный текст.
- `Number` — числовой input.
- `Email` — email field + email validation helper workflow.
- `Checkbox` — checkbox со значениями checked/unchecked.
- `Switcher` — Bitrix switch/boolean-like toggler.
- `Select` — select с `options()` и resolver-ами.
- `TagSelect` / `DialogSelect` / `EntitySelect` / `UserSelect` — selector-поля на базе Bitrix UI selector.
- `IblockSelect` / `IblockSectionSelect` / `IblockElementSelect` — селекторы Bitrix iblock сущностей.
- `Date` / `DateTime` — даты/дата-время.
- `File` / `Image` — файл/изображение.
- `Password` — пароль/секрет; поддерживает сохранение старого значения при пустом POST.
- `Hidden` — hidden input.
- `Html` — HTML/block output field.
- `Preview` — preview-only рендер.
- `Color` — цвет.
- `Slug` — slug-поле с `from()` и `separator()`.
- `UfField` — адаптация UF-полей.

## 20) Relation fields

Relation-поля (`BelongsTo`, `HasOne`, `HasMany`, `BelongsToMany`) находятся в `MB\Bitrix\AdminKit\Field\Relation\*`, работают с ORM/DataManagerResource и имеют отдельные сценарии persistence/preview.

Подробно: `docs/user/guides/relations.md`.
