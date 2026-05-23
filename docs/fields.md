# Fields

`Field` — это декларативное описание поля в AdminKit.

Поля используются в:
- `indexFields()`
- `formFields()`
- `detailFields()`
- `fields()` (например, в `OptionsPage`)
- `components()` (когда поля встраиваются в layout-компоненты)

Основная настройка полей делается через fluent chain. Внутренние getter/checker/render/runtime методы обычно не нужны в пользовательском коде.

## 1. Быстрый пример

```php
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Switcher;

public function indexFields(): iterable
{
    return [
        ID::make('ID'),

        Text::make('Название', 'NAME')
            ->sortable()
            ->asEditLink(),

        Switcher::make('Активность', 'ACTIVE')
            ->values('Y', 'N'),
    ];
}

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
```

- Первый аргумент `make()` — `label` (подпись в UI).
- Второй аргумент — `column` (ключ данных/ORM-поля/option key).
- Если `column` не передан, он генерируется из `label`, но для ORM и `OptionsPage` лучше указывать `column` явно.

## 2. Создание поля

```php
Text::make('Название', 'NAME');
Text::make('SEO title');
```

- `make()` создает экземпляр поля.
- `label` используется в интерфейсе.
- `column` используется для чтения/сохранения значения.
- Если `column` не задан, он генерируется автоматически.
- Для предсказуемости лучше задавать `column` явно.

## 3. Базовые fluent-методы всех полей

### Значение и значение по умолчанию

```php
->default('Y')
->fill($value)
->setValue($value)
```

- `default()` задает fallback-значение.
- `fill()` / `setValue()` обычно нужны редко (например, при ручной подготовке поля).

```php
Switcher::make('Активность', 'ACTIVE')
    ->values('Y', 'N')
    ->default('Y');
```

### Видимость

```php
->hideOn(PageType::INDEX)
->showOn(PageType::FORM, PageType::DETAIL)
->visible(...)
->canSee(...)
->visibleWhen(...)
```

```php
Text::make('Внутренний комментарий', 'INTERNAL_COMMENT')
    ->hideOn(PageType::INDEX);
```

```php
use MB\Bitrix\AdminKit\Field\FieldConditionContext;

Text::make('Описание', 'DESCRIPTION')
    ->canSee(fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'));
```

- `visible()` — базовый метод для управления видимостью.
- `canSee()` — алиас в стиле MoonShine/Nova.
- `visibleWhen()` — короткая запись условий по значениям других полей.

### Required / validation

```php
->required()
->required(fn (FieldConditionContext $ctx): bool => ...)
->requiredWhen('ACTIVE', 'Y')
->validate(fn ($value, array $data) => ...)
->minLength()
->maxLength()
->email()
->url()
->numeric()
->min()
->max()
->pattern()
->in()
```

```php
Text::make('Email', 'EMAIL')
    ->required()
    ->email();
```

```php
Text::make('Комментарий', 'COMMENT')
    ->required(
        fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'),
        dependsOn: 'ACTIVE'
    );
```

### Readonly

```php
->readonly()
->readonly(fn (FieldConditionContext $ctx): bool => ...)
->readonlyWhen('ACTIVE', 'N')
->readonlyOnCreate()
->readonlyOnUpdate()
```

```php
Text::make('Код', 'CODE')
    ->readonlyOnUpdate();
```

```php
Text::make('Код', 'CODE')
    ->readonly(fn (FieldConditionContext $ctx): bool => $ctx->isEdit());
```

### Help UI

```php
->placeholder('...')
->help('...')
->hint('...')
```

```php
Text::make('Email', 'EMAIL')
    ->placeholder('admin@example.com')
    ->help('Используется для системных уведомлений');
```

- `placeholder` применяется в input/textarea-полях.
- `hint`/`help` используются для подсказок.
- В текущем API `help()` также заполняет `hint`.

### Formatting

```php
->format(fn ($value) => ...)
->preview(fn ($value) => ...)
->displayUsing(fn ($value, array $row, array $context) => ...)
```

```php
Text::make('Цена', 'PRICE')
    ->displayUsing(fn ($value): string => number_format((float) $value, 2, '.', ' ') . ' ₽');
```

```php
Text::make('Описание', 'DESCRIPTION')
    ->preview(fn ($value): string => mb_strimwidth((string) $value, 0, 80, '...'));
```

- `displayUsing()` — основной способ кастомизировать отображение (`value`, `row`, `context`).
- `format()` — упрощенный formatter только по `value`.
- `preview()` — короткое представление значения.

### Grid

```php
->sortable()
->sortable(false)
->editable()
->asEditLink()
->linkToEdit()
```

```php
Text::make('Название', 'NAME')
    ->sortable()
    ->asEditLink();
```

- `sortable()` включает сортировку по `column`.
- `asEditLink()` / `linkToEdit()` превращают значение в ссылку на редактирование.
- `editable()` включает inline edit, если это поддерживает и поле, и grid.
- `computed()` автоматически отключает сортировку.

### ORM select / computed

```php
->selectable(false)
->selectColumns(['FIELD_1', 'FIELD_2'])
->computed(fn (array $row) => ...)
```

```php
Text::make('Полное имя', 'FULL_NAME')
    ->computed(fn (array $row): string => trim(($row['LAST_NAME'] ?? '') . ' ' . ($row['NAME'] ?? '')))
    ->selectColumns(['LAST_NAME', 'NAME']);
```

- `computed()` для значений, которых нет отдельной ORM-колонкой.
- `computed()` отключает сортировку.
- `selectColumns()` помогает загрузить исходные поля для вычисления.

### Export / Import

```php
->exportable(false)
->private()
->importable(false)
->system()
```

```php
Password::make('API key', 'API_KEY')
    ->private()
    ->exportable(false);

ID::make('ID')
    ->system()
    ->importable(false);
```

- `private()` — чувствительное поле.
- `exportable(false)` — исключить из экспорта.
- `importable(false)` — исключить из импорта.
- `system()` — служебное поле.

### Multiple / post value

```php
->multiple()
```

```php
Select::make('Теги', 'TAGS')
    ->multiple();
```

- `multiple()` включает множественное значение там, где поле это поддерживает.

## 4. Условная логика

```php
->required(fn (FieldConditionContext $ctx): bool => ...)
->readonly(fn (FieldConditionContext $ctx): bool => ...)
->visible(fn (FieldConditionContext $ctx): bool => ...)
->canSee(fn (FieldConditionContext $ctx): bool => ...)
->when(condition, modifier, dependsOn)
```

```php
Text::make('Комментарий', 'COMMENT')
    ->required(
        fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'),
        dependsOn: 'ACTIVE'
    );
```

```php
Text::make('SEO title', 'SEO_TITLE')
    ->when(
        condition: fn (FieldConditionContext $ctx): bool => $ctx->is('SEO_ENABLED', 'Y'),
        modifier: fn (Text $field): Text => $field
            ->required()
            ->help('Заполните SEO title'),
        dependsOn: 'SEO_ENABLED',
    );
```

- Без `dependsOn` условие работает серверно.
- С `dependsOn` условие работает серверно + реактивно.
- `dependsOn` может быть строкой или массивом.
- `modifier` должен быть идемпотентным (без накопления повторных side-effects).

`FieldConditionContext` обычно достаточно использовать через:
- `get()`
- `has()`
- `is()`
- `isNot()`
- `in()`
- `isCreate()`
- `isEdit()`

## 5. Реактивность

```php
Text::make('Комментарий', 'COMMENT')
    ->dependsOn('ACTIVE', function (Text $field, mixed $value, array $formData): void {
        if (($formData['ACTIVE'] ?? null) === 'Y') {
            $field->required();
        }
    });
```

```php
Text::make('Комментарий', 'COMMENT')
    ->dependsOn(['ACTIVE', 'TYPE'], function (Text $field, mixed $value, array $formData): void {
        if (($formData['ACTIVE'] ?? null) === 'Y' && ($formData['TYPE'] ?? null) === 'manual') {
            $field->required();
        }
    });
```

- `dependsOn` принимает одно или несколько полей.
- callback получает полный `formData`.
- Для multi-field условий лучше опираться на `formData`, а не только на `$value`.
- Во frontend зависимости обрабатываются с debounce.

## 6. Стандартные поля

Ниже — краткий обзор основных полей из `src/Field/*.php`.

### Text

```php
Text::make('Название', 'NAME')
    ->required()
    ->maxLength(255);
```

### Textarea

```php
Textarea::make('Описание', 'DESCRIPTION')
    ->rows(6);
```

### Number

```php
Number::make('Сортировка', 'SORT')
    ->min(0);
```

### Email

```php
Email::make('Email', 'EMAIL')
    ->required();
```

### Checkbox

```php
Checkbox::make('Опубликовано', 'PUBLISHED')
    ->values('Y', 'N');
```

### Switcher

```php
Switcher::make('Активность', 'ACTIVE')
    ->values('Y', 'N')
    ->default('Y');
```

### Select

```php
Select::make('Тип', 'TYPE')
    ->options([
        'product' => 'Товар',
        'service' => 'Услуга',
    ]);
```

### Date / DateTime

```php
Date::make('Дата начала', 'DATE_FROM');
DateTime::make('Обновлено', 'UPDATED_AT');
```

### File / Image

```php
File::make('Файл', 'FILE_ID');
Image::make('Изображение', 'PREVIEW_PICTURE');
```

### Password

```php
Password::make('API key', 'API_KEY')
    ->preserveStoredValueWhenEmpty()
    ->private();
```

### Hidden

```php
Hidden::make('Токен', 'TOKEN');
```

### Html / Preview / Color

```php
Html::make('Блок', 'HTML_BLOCK');
Preview::make('Превью', 'SUMMARY');
Color::make('Цвет', 'COLOR');
```

### Slug

```php
Slug::make('Код', 'CODE');
```

```php
Slug::make('Код', 'CODE')
    ->from('NAME')
    ->separator('-');
```

Без `from()` `Slug` работает как обычное текстовое поле (сохраняет ручной ввод). Автогенерация включается через `from()`.

### Entity selectors и iblock selectors

```php
UserSelect::make('Пользователь', 'USER_ID');
EntitySelect::make('Элемент', 'ITEM_ID');
IblockSelect::make('Инфоблок', 'IBLOCK_ID');
IblockSectionSelect::make('Раздел', 'SECTION_ID');
IblockElementSelect::make('Элемент инфоблока', 'ELEMENT_ID');
TagSelect::make('Теги', 'TAGS');
DialogSelect::make('Связи', 'LINKS');
```

### ID и UfField

```php
ID::make('ID');
UfField::make('UF_CUSTOM_FIELD', 'UF_CUSTOM_FIELD');
```

## 7. Relation fields

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
```

- Relation fields работают с `DataManagerResource`.
- Используют связи Bitrix D7 ORM.
- Сохраняются через `EntityObject`.
- Подробности: `docs/user/guides/relations.md`.

## 8. Создание собственного поля

```php
use MB\Bitrix\AdminKit\Field\Field;

final class ColorField extends Field
{
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        $name = htmlspecialcharsbx($this->getColumn());
        $value = htmlspecialcharsbx((string) $this->resolveValue($value));

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="color" class="ui-ctl-element" name="{$name}" value="{$value}">
        </div>
        HTML;
    }

    public function normalize(mixed $value): mixed
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
```

Обычно достаточно:
- переопределить `renderFormField()`;
- при необходимости — `normalize()` для преобразования POST-значения.

`label`/errors/hint/wrapper рендерятся через `FieldRowRenderer`, поэтому не нужно дублировать этот UI-каркас внутри поля.

## 9. Что не является пользовательским Fluent API

Следующие методы обычно не вызываются напрямую в `Resource`/`Page`:

- `getColumn()`
- `getLabel()`
- `getValue()`
- `getDefault()`
- `getSelectColumns()`
- `getGridColumnConfig()`
- `isRequired()`
- `isReadOnly()`
- `isVisibleOn()`
- `runValidation()`
- `renderForm()`
- `renderIndex()`
- `renderDetail()`
- `applyDependency()`
- `displayValue()`
- `previewValue()`

Эти методы используются внутренними renderer-ами, grid adapters, DataPipeline и form handlers.

Важно: `displayValue()` и `previewValue()` — это не fluent-настройка поля. Они применяют callbacks, заданные через `displayUsing()`, `format()` и `preview()`.
