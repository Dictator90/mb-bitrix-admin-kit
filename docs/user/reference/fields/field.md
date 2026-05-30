# Field (общий API)

Базовый класс: `MB\Bitrix\AdminKit\Field\Field`.

## Создание

```php
Text::make('Название', 'NAME');
```

## Ключевые методы

Только методы, которые влияют на поведение поля, отображение или обработку данных:

- Базовое поведение: `multiple()`, `selectable()`, `selectColumns()`
- Значение и дефолт: `setValue()`, `fill()`, `default()`
- Видимость: `visible()`, `showOn()`, `hideOn()`, `visibleWhen()`
- Readonly-логика: `readonly()`, `readonlyWhen()`, `readonlyOnCreate()`, `readonlyOnUpdate()`
- Валидация: `required()`, `requiredWhen()`, `minLength()`, `maxLength()`, `email()`, `url()`, `numeric()`, `min()`, `max()`, `pattern()`, `in()`
- Форматирование вывода: `format()`, `preview()`, `displayUsing()`
- UI-подсказки: `hint()` (тултип-«?» у лейбла поля), `placeholder()` (плейсхолдер инпута)
- Реактивность: `dependsOn()`, `onChange()`
- Грид-метаданные: `sortable()`, `editable()`, `asEditLink()`
- Грид-колонка (оформление): `width(int)`, `align('left'|'center'|'right')`, `color(?string)`, `sticked(bool)`
- Import/Export-флаги: `importable()`, `system()`, `exportable()`, `private()`
- Рендер-хуки: `renderForm()`, `renderFormField()`, `renderIndex()`, `renderDetail()`
## Нормализация и сериализация

- `normalize($value)` — базовое приведение значения для формы.
- `serializePostValue($value)` — значение, которое уйдет в persistence.
- Для multiple-полей базовый `Field` работает с массивом, конкретные поля могут переопределять логику.

## Ограничения

- Для relation-полей используйте `MB\Bitrix\AdminKit\Field\Relation\*`.
- Для сложных relation/entity selector сценариев не полагайтесь на inline-edit в гриде.

## Значения по умолчанию

Базовый `Field` и его трейты задают следующие дефолты (их можно переопределять методами):

- `multiple = false`
- `selectable = true`
- `selectColumns = null` (фактически используется `[$field->getColumn()]`)
- `required = false`
- `readonly = false`
- `visible = true`
- `sortable = true`
- `editable = false`
- `default = null`
- `value = null`
- `placeholder = null`
- `exportable = true`
- `importable = true`
- `preserveStoredValueWhenEmpty() = false`

Отдельные поля могут переопределять эти значения (например relation-поля, `Hidden`, `ID`, `Password`).
