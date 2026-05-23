# Fields

`Field` — декларативное описание UI-поля в AdminKit. Обычно поля объявляются в `indexFields()`, `formFields()`, `detailFields()`, а также в `fields()` у `OptionsPage`.

Подробная документация по каждому конкретному полю вынесена в отдельные страницы: [docs/user/reference/fields/README.md](user/reference/fields/README.md).

## 1) Быстрый пример

```php
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Switcher;

public function indexFields(): iterable
{
    return [
        ID::make('ID'),
        Text::make('Название', 'NAME')->sortable()->asEditLink(),
        Switcher::make('Активность', 'ACTIVE')->values('Y', 'N'),
    ];
}
```

## 2) Создание поля через `make()`

```php
Text::make('Название', 'NAME');
Text::make('SEO title');
```

- 1-й аргумент: `label` (подпись).
- 2-й аргумент: `column` (ключ данных/ORM-поле).
- Если `column` не задан, он генерируется автоматически; для ORM/OptionsPage лучше задавать явно.

## 3) Общий Fluent API

Большинство полей поддерживает общий набор fluent-методов. Ниже описаны методы, которые обычно используются в Resource / OptionsPage. Подробности по конкретным полям — в отдельных страницах из каталога ниже.

### 3.1 Значение по умолчанию

#### `default()`
Где применяется: `form`, `options`, иногда `detail/index` как fallback.

Что делает: задает fallback-значение, если в item/row/form data нет значения.

```php
Switcher::make('Активность', 'ACTIVE')
    ->values('Y', 'N')
    ->default('Y');
```

#### `fill()` / `setValue()`
Где применяется: ручная подготовка поля на кастомных страницах; в обычных Resource обычно используются редко.

Что делает: позволяет вручную выставить текущее значение поля до рендера.

```php
Text::make('Заголовок', 'TITLE')
    ->fill('Черновик');
```

### 3.2 Видимость

#### `hideOn()`
Где применяется: `index`, `form`, `detail`, `options` (если рендер учитывает `PageType`).

Что делает: скрывает поле на указанных типах страниц.

```php
use MB\Bitrix\AdminKit\Support\Enums\PageType;

Text::make('Внутренний комментарий', 'INTERNAL_COMMENT')
    ->hideOn(PageType::INDEX);
```

#### `showOn()`
Где применяется: `index`, `form`, `detail`, `options`.

Что делает: показывает поле только на указанных типах страниц.

```php
Text::make('Описание', 'DESCRIPTION')
    ->showOn(PageType::FORM, PageType::DETAIL);
```

#### `visible()`
Где применяется: `form`, `options`, `detail`, `index`.

Что делает: управляет видимостью поля (`bool` или `Closure`).

```php
use MB\Bitrix\AdminKit\Field\FieldConditionContext;

Text::make('Описание', 'DESCRIPTION')
    ->visible(fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'));
```

#### `canSee()`
Где применяется: то же, что `visible()`.

Что делает: alias для `visible()`.

```php
Text::make('Комментарий', 'COMMENT')
    ->canSee(fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'));
```

#### `visibleWhen()`
Где применяется: `form`, `options`, особенно для простой условной видимости.

Что делает: shortcut для условий вида «показывать поле, когда значение другого поля соответствует условию». Поддерживает `reactive`-режим 4-м аргументом.

```php
Text::make('Причина отключения', 'DISABLE_REASON')
    ->visibleWhen('ACTIVE', '=', 'N', true);
```

### 3.3 Required и validation

#### `required()`
Где применяется: `form`, `options`, `import` (когда используется общий pipeline валидации).

Что делает: делает поле обязательным (`bool` или `Closure`).

```php
Text::make('Название', 'NAME')
    ->required();

Text::make('Комментарий', 'COMMENT')
    ->required(
        fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'),
        dependsOn: 'ACTIVE'
    );
```

#### `requiredWhen()`
Где применяется: `form`, `options`, `import`.

Что делает: shortcut для обязательности по условию; также поддерживает `reactive` 4-м аргументом.

```php
Text::make('Комментарий', 'COMMENT')
    ->requiredWhen('ACTIVE', '=', 'Y', true);
```

#### `validate()`
Где применяется: `form`, `options`, `import`.

Что делает: в fluent-режиме принимает `Closure`-валидатор и добавляет его в цепочку.

```php
Text::make('Артикул', 'ARTICLE')
    ->validate(static function (mixed $value): string|false|null {
        if ($value === null || $value === '') {
            return 'Артикул обязателен';
        }

        return null;
    });
```

#### `minLength()` / `maxLength()`
Где применяется: `form`, `options`, `import`.

```php
Text::make('Название', 'NAME')
    ->minLength(3)
    ->maxLength(255);
```

#### `email()`
Где применяется: `form`, `options`, `import`.

```php
Text::make('Email', 'EMAIL')
    ->required()
    ->email();
```

#### `url()`
Где применяется: `form`, `options`, `import`.

```php
Text::make('Сайт', 'URL')
    ->url();
```

#### `numeric()` / `min()` / `max()`
Где применяется: `form`, `options`, `import`.

```php
Number::make('Сортировка', 'SORT')
    ->numeric()
    ->min(0)
    ->max(1000);
```

#### `pattern()`
Где применяется: `form`, `options`, `import`.

```php
Text::make('Код', 'CODE')
    ->pattern('/^[a-z0-9_-]+$/', 'Допустимы только латинские буквы, цифры, _ и -');
```

#### `in()`
Где применяется: `form`, `options`, `import`.

```php
Select::make('Тип', 'TYPE')
    ->options([
        'product' => 'Товар',
        'service' => 'Услуга',
    ])
    ->in(['product', 'service']);
```

### 3.4 Readonly

#### `readonly()`
Где применяется: `form`, `options`.

Что делает: блокирует редактирование (можно передать `Closure` для условного режима).

```php
Text::make('Код', 'CODE')
    ->readonly();

Text::make('Код', 'CODE')
    ->readonly(fn (FieldConditionContext $ctx): bool => $ctx->isEdit());
```

#### `readonlyWhen()`
Где применяется: `form`, `options`.

Что делает: shortcut для readonly по условию; поддерживает `reactive` 4-м аргументом.

```php
Text::make('Код', 'CODE')
    ->readonlyWhen('ACTIVE', '=', 'N', true);
```

#### `readonlyOnCreate()`
Где применяется: form create.

```php
Text::make('Внешний ID', 'XML_ID')
    ->readonlyOnCreate();
```

#### `readonlyOnUpdate()`
Где применяется: form edit.

```php
Text::make('Символьный код', 'CODE')
    ->readonlyOnUpdate();
```

### 3.5 Help UI

#### `placeholder()`
Где применяется: `form`, `options`.

```php
Text::make('Email', 'EMAIL')
    ->placeholder('admin@example.com');
```

#### `help()`
Где применяется: `form`, `options`.

Что делает: задает help-текст и одновременно заполняет `hint`.

```php
Text::make('Email', 'EMAIL')
    ->help('Используется для системных уведомлений');
```

#### `hint()`
Где применяется: `form`, `options`.

```php
Text::make('Код', 'CODE')
    ->hint('Используется в URL');
```

### 3.6 Formatting / отображение

#### `displayUsing()`
Где применяется: `index`, `detail`, `export` (когда адаптеры используют display-значение).

Что делает: настраивает отображение через callback `(value, row, context)`.

```php
Text::make('Цена', 'PRICE')
    ->displayUsing(fn ($value): string => number_format((float) $value, 2, '.', ' ') . ' ₽');
```

#### `format()`
Где применяется: `index`, `detail`.

Что делает: простой formatter по `value`.

```php
Text::make('Название', 'NAME')
    ->format(fn ($value): string => mb_strtoupper((string) $value));
```

#### `preview()`
Где применяется: `index`, `detail`.

Что делает: формирует краткое превью значения.

```php
Text::make('Описание', 'DESCRIPTION')
    ->preview(fn ($value): string => mb_strimwidth((string) $value, 0, 80, '...'));
```

`displayValue()` и `previewValue()` — не fluent API. Это runtime-методы рендера, которые применяют `displayUsing()` / `format()` / `preview()`.

### 3.7 Grid

#### `sortable()`
Где применяется: index grid.

```php
Text::make('Название', 'NAME')
    ->sortable();

Text::make('Описание', 'DESCRIPTION')
    ->sortable(false);
```

#### `editable()`
Где применяется: index grid inline edit.

```php
Text::make('Название', 'NAME')
    ->editable();
```

#### `asEditLink()`
Где применяется: index grid.

```php
Text::make('Название', 'NAME')
    ->asEditLink();
```

#### `linkToEdit()`
Где применяется: index grid.

Что делает: alias для `asEditLink()`.

```php
Text::make('Название', 'NAME')
    ->linkToEdit();
```

### 3.8 ORM select / computed

#### `selectable()`
Где применяется: index/detail/form loading.

Что делает: включает или отключает выбор базовой колонки в ORM select.

```php
Text::make('Вычисляемое поле', 'CUSTOM')
    ->selectable(false);
```

#### `selectColumns()`
Где применяется: index/detail/form loading для computed/display полей.

Что делает: указывает дополнительные колонки, необходимые для вычисления/отображения.

```php
Text::make('Полное имя', 'FULL_NAME')
    ->computed(fn (array $row): string => trim(($row['LAST_NAME'] ?? '') . ' ' . ($row['NAME'] ?? '')))
    ->selectColumns(['LAST_NAME', 'NAME']);
```

#### `computed()`
Где применяется: `index`, `detail`, иногда `form` для readonly-вывода.

Что делает: вычисляет значение по строке данных и автоматически отключает сортировку.

### 3.9 Export / Import

#### `exportable()`
Где применяется: export.

```php
Password::make('API key', 'API_KEY')
    ->exportable(false);
```

#### `private()`
Где применяется: export/security conventions.

```php
Password::make('API key', 'API_KEY')
    ->private();
```

#### `importable()`
Где применяется: import.

```php
ID::make('ID')
    ->importable(false);
```

#### `system()`
Где применяется: import/export/UI conventions.

```php
ID::make('ID')
    ->system();
```

### 3.10 Multiple

#### `multiple()`
Где применяется: `form`, `options`, `import` у полей с множественным вводом.

```php
Select::make('Теги', 'TAGS')
    ->multiple();
```

### 3.11 Реактивность

#### `dependsOn()`
Где применяется: `form`, `options` reactive UI.

Что делает: связывает поле с одним или несколькими источниками и применяет modifier при изменении.

```php
Text::make('Комментарий', 'COMMENT')
    ->dependsOn(['ACTIVE', 'TYPE'], function (Text $field, mixed $value, array $formData): void {
        if (($formData['ACTIVE'] ?? null) === 'Y' && ($formData['TYPE'] ?? null) === 'manual') {
            $field->required();
        }
    });
```

### 3.12 Условная магия `when()`

Где применяется: `form/options` render + validation; reactive-поведение — если указан `dependsOn`.

Что делает: применяет modifier к полю, когда `condition` возвращает `true`.

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

## 4) Стандартные поля (краткий каталог)

Подробности по каждому полю — на отдельных страницах.

| Поле | Назначение | Документация |
| --- | --- | --- |
| `ID` | Идентификатор записи | [ID](user/reference/fields/id.md) |
| `Text` | Однострочный текст | [Text](user/reference/fields/text.md) |
| `Textarea` | Многострочный текст | [Textarea](user/reference/fields/textarea.md) |
| `Number` | Числовое поле | [Number](user/reference/fields/number.md) |
| `Email` | E-mail с валидацией | [Email](user/reference/fields/email.md) |
| `Checkbox` | Флаг/чекбокс | [Checkbox](user/reference/fields/checkbox.md) |
| `Switcher` | Переключатель Y/N | [Switcher](user/reference/fields/switcher.md) |
| `Select` | Выбор из списка | [Select](user/reference/fields/select.md) |
| `Date` | Дата | [Date](user/reference/fields/date.md) |
| `DateTime` | Дата и время | [DateTime](user/reference/fields/datetime.md) |
| `File` | Файл | [File](user/reference/fields/file.md) |
| `Image` | Изображение | [Image](user/reference/fields/image.md) |
| `Password` | Пароль/секрет | [Password](user/reference/fields/password.md) |
| `Hidden` | Скрытое поле | [Hidden](user/reference/fields/hidden.md) |
| `Html` | HTML-контент | [Html](user/reference/fields/html.md) |
| `Preview` | Превью/readonly-визуализация | [Preview](user/reference/fields/preview.md) |
| `Color` | Цветовое значение | [Color](user/reference/fields/color.md) |
| `Slug` | Символьный код | [Slug](user/reference/fields/slug.md) |
| `UserSelect` | Выбор пользователя | [UserSelect](user/reference/fields/user-select.md) |
| `EntitySelect` | Универсальный entity selector | [EntitySelect](user/reference/fields/entity-select.md) |
| `IblockSelect` | Выбор инфоблока | [IblockSelect](user/reference/fields/iblock-select.md) |
| `IblockSectionSelect` | Выбор раздела ИБ | [IblockSectionSelect](user/reference/fields/iblock-section-select.md) |
| `IblockElementSelect` | Выбор элемента ИБ | [IblockElementSelect](user/reference/fields/iblock-element-select.md) |
| `TagSelect` | Теги/множественный selector | [TagSelect](user/reference/fields/tag-select.md) |
| `DialogSelect` | Диалоговый selector | [DialogSelect](user/reference/fields/dialog-select.md) |
| `UfField` | Адаптер UF-полей | [UfField](user/reference/fields/uf-field.md) |

### Slug (важное поведение)

```php
Slug::make('Код', 'CODE');

Slug::make('Код', 'CODE')
    ->from('NAME')
    ->separator('-');
```

- Без `from()` поле работает как обычный `Text` (автогенерация не включается).
- C `from()` значение генерируется реактивно из зависимых полей.
- Генерация slug использует `AdminString::slug(...)` (внутри поддерживается Bitrix-совместимая транслитерация с fallback-логикой).

Подробности: [Slug](user/reference/fields/slug.md).

## 5) Relation fields (кратко)

Relation-поля:
- [BelongsTo](user/reference/fields/belongs-to.md)
- [HasOne](user/reference/fields/has-one.md)
- [HasMany](user/reference/fields/has-many.md)
- [BelongsToMany](user/reference/fields/belongs-to-many.md)

Общий гайд по связям: [Relations guide](user/guides/relations.md).

## 6) Создание собственного поля

Точка расширения — наследование от `Field`/существующих concrete-полей и переопределение extension points (например, `renderFormField()`, `normalize()`, `resolveValue()`, `getColumn()`), когда это действительно нужно.

Практический пример: [Cookbook: Field](user/cookbook/field.md).

## 7) Что **не** является пользовательским Fluent API

Не документируйте как fluent-настройки внутренние runtime/getter-методы (`getColumn()`, `getLabel()`, `getValue()`, `getDefault()`, `getSelectColumns()`, `getGridColumnConfig()`, `getGridColumnType()`, `getFilterType()`, `isVisibleOn()`, `isRequired()`, `isReadOnly()`, `isReadOnlyFor()`, `isExportable()`, `isImportable()`, `isPrivate()`, `isSystem()`, `hasDependency()`, `getDependsOn()`, `applyDependency()`, `displayValue()`, `previewValue()`, `renderIndex()`, `renderForm()`, `renderDetail()`, `runValidation()`, `resolveValue()`).

`displayValue()` и `previewValue()` — не fluent API. Для пользовательской настройки отображения используйте `displayUsing()`, `format()` и `preview()`.
