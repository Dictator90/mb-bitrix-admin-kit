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

## 3) Общий Fluent API (для большинства полей)

Ниже — обзор пользовательских методов настройки, которые используются в прикладном коде.

- Значения: `default()`.
- Валидация/состояние: `required()`, `readonly()`.
- Видимость и условия: `visible()`, `canSee()`, `when()`.
- Реактивность: `dependsOn()`.
- UI-помощь: `placeholder()`, `help()`, `hint()`.
- Отображение: `format()`, `preview()`, `displayUsing()`.
- Grid: `sortable()`, `editable()`, `asEditLink()`.
- Вычисляемые поля: `computed()`, `selectColumns()`.
- Import/Export/служебные флаги: `exportable()`, `importable()`, `private()`, `system()`.
- Множественные значения: `multiple()`.

Подробно с примерами: [Field (базовый класс)](user/reference/fields/field.md).

## 4) Условная логика

```php
Text::make('Комментарий', 'COMMENT')
    ->visible(fn ($ctx) => $ctx->is('ACTIVE', 'Y'))
    ->required(fn ($ctx) => $ctx->isCreate());
```

## 5) Реактивность

```php
Slug::make('Код', 'CODE')
    ->from('NAME')
    ->dependsOn('TYPE', function (Slug $field, mixed $value): void {
        if ($value === 'service') {
            $field->separator('_');
        }
    });
```

## 6) Стандартные поля (краткий каталог)

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

## 7) Relation fields (кратко)

Relation-поля:
- [BelongsTo](user/reference/fields/belongs-to.md)
- [HasOne](user/reference/fields/has-one.md)
- [HasMany](user/reference/fields/has-many.md)
- [BelongsToMany](user/reference/fields/belongs-to-many.md)

Общий гайд по связям: [Relations guide](user/guides/relations.md).

## 8) Создание собственного поля

Точка расширения — наследование от `Field`/существующих concrete-полей и переопределение extension points (например, `renderFormField()`, `normalize()`, `resolveValue()`, `getColumn()`), когда это действительно нужно.

Практический пример: [Cookbook: Field](user/cookbook/field.md).

## 9) Что **не** является пользовательским Fluent API

Не документируйте как fluent-настройки внутренние runtime/getter-методы (`getColumn()`, `getValue()`, `isRequired()`, `renderIndex()` и т.д.).

Также `displayValue()` и `previewValue()` — runtime-методы рендера, а не методы пользовательской настройки. Для прикладного кода используйте `displayUsing()`, `format()` и `preview()`.
