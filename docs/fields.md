# Fields

## Что это

`Field` в Admin Kit — это декларация одного бизнес-атрибута ресурса (например, `NAME`, `ACTIVE`, `SORT`, `PREVIEW_PICTURE`) и правила его отображения/обработки в UI и pipeline.

Поле обычно отвечает сразу за несколько задач:

- рендер значения в grid (`index`);
- рендер input в форме (`form`);
- рендер read-only значения (`detail`);
- нормализацию и сериализацию POST-значения;
- валидацию и видимость;
- участие в import/export (если включено).

## Где используются поля

| Контекст | Что означает |
|---|---|
| `index` | колонка в `bitrix:main.ui.grid` |
| `form` | input в create/edit форме |
| `detail` | read-only отображение записи |
| `options` | поле на `OptionsPage` |
| `filter` | фильтрация задается через `Filter`, но column/семантика часто совпадают с полями |
| `import` | значение проходит normalize/validate через form pipeline |
| `export` | значение попадает в экспортируемую строку |

## Базовый пример

```php
<?php

namespace App\Admin\Resource;

use App\Bitrix\Table\ProductTable;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;

final class ProductResource extends DataManagerResource
{
    public static function label(): string
    {
        return 'Товары';
    }

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID', 'ID')->sortable(),
            Text::make('Название', 'NAME')->sortable(),
            Switcher::make('Активен', 'ACTIVE'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
            Switcher::make('Активен', 'ACTIVE')->default('Y'),
        ];
    }
}
```

## `make()`: label и column

Все поля создаются через статический конструктор:

```php
Text::make('Название', 'NAME')
```

- первый аргумент — `label` (подпись для UI);
- второй аргумент — `column` (ключ в ORM/данных формы);
- если `column` не задан, он вычисляется из label (через safe key).

## Общий fluent API

Ниже перечислены методы, которые реально есть в `Field`/traits и часто используются в пользовательском коде.

| Метод | index | form | detail | options | import | export | Что делает |
|---|---:|---:|---:|---:|---:|---:|---|
| `default()` | - | ✅ | - | ✅ | ✅ | - | Значение по умолчанию |
| `setValue()` / `fill()` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | Установка значения/заполнение из row |
| `hideOn()` / `showOn()` | ✅ | ✅ | ✅ | ✅ | - | - | Контекстная видимость |
| `visible()` / `canSee()` / `visibleWhen()` | ✅ | ✅ | ✅ | ✅ | - | - | Условная видимость |
| `required()` / `requiredWhen()` | - | ✅ | - | ✅ | ✅ | - | Обязательность |
| `validate()` | - | ✅ | - | ✅ | ✅ | - | Кастомные валидаторы |
| `minLength()` / `maxLength()` | - | ✅ | - | ✅ | ✅ | - | Ограничения длины |
| `email()` / `url()` / `numeric()` | - | ✅ | - | ✅ | ✅ | - | Типовые правила |
| `min()` / `max()` / `pattern()` / `in()` | - | ✅ | - | ✅ | ✅ | - | Ограничения значения |
| `readonly()` / `readonlyWhen()` | - | ✅ | ✅ | ✅ | - | - | Read-only поведение |
| `readonlyOnCreate()` / `readonlyOnUpdate()` | - | ✅ | - | - | - | - | Read-only в режиме create/edit |
| `placeholder()` / `help()` / `hint()` | - | ✅ | - | ✅ | - | - | UX-подсказки |
| `displayUsing()` / `format()` / `preview()` | ✅ | ✅ | ✅ | ✅ | - | ✅ | Форматирование/преобразование |
| `sortable()` / `editable()` / `asEditLink()` | ✅ | - | - | - | - | - | Grid-поведение |
| `selectable()` / `selectColumns()` / `computed()` | ✅ | - | - | - | - | ✅ | Участие в SELECT/экспорте |
| `exportable()` / `importable()` | - | - | - | - | ✅ | ✅ | Участие в import/export |
| `private()` / `system()` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | Служебные флаги |
| `multiple()` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | Множественное значение |
| `dependsOn()` | - | ✅ | - | ✅ | - | - | Реактивные зависимости |
| `when()` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | Условная настройка fluent API |

> `displayValue()` и `previewValue()` — runtime-методы рендера. Обычно вы настраиваете их через fluent API (`displayUsing()`, `preview()`, `format()`), а не вызываете вручную в ресурсах.

## Каталог полей

| Поле | Когда использовать | Документация |
|---|---|---|
| `ID` | Первичный ключ, служебный идентификатор | [ID](user/reference/fields/id.md) |
| `Text`, `Textarea`, `Slug` | Названия, описания, slug | [Fields reference](user/reference/fields/README.md) |
| `Number`, `Date`, `DateTime` | Числа, даты, даты+время | [Fields reference](user/reference/fields/README.md) |
| `Select`, `Checkbox`, `Switcher` | Enum/флаги/булевы значения | [Fields reference](user/reference/fields/README.md) |
| `File`, `Image` | Загрузка файлов и изображений | [Fields reference](user/reference/fields/README.md) |
| `EntitySelect` family | Селекторы Bitrix UI entity-selector | [Fields reference](user/reference/fields/README.md) |
| `UfField` | Обертка для UF-полей Bitrix | [UfField](user/reference/fields/uf-field.md) |

## Relation fields

Для ORM-связей используйте специальные поля:

- [BelongsTo](user/reference/fields/belongs-to.md)
- [HasOne](user/reference/fields/has-one.md)
- [HasMany](user/reference/fields/has-many.md)
- [BelongsToMany](user/reference/fields/belongs-to-many.md)

Они работают поверх D7 ORM relation metadata и должны настраиваться через relation API (`relation()`, `table()`, `foreignKey()`, `localKey()`, `value()`, `filter()`, `order()`).

## Custom fields

Создавайте собственный field-класс, когда:

- нужен специфичный Bitrix UI input;
- нужно особое normalize/serialize поведение;
- нужно единообразно переиспользовать бизнес-форматирование.

Базовый путь:

1. Наследоваться от `MB\Bitrix\AdminKit\Field\Field`.
2. Реализовать `renderFormField()`.
3. При необходимости переопределить `normalize()`/`serializePostValue()`.
4. Добавить документацию на страницу в `docs/user/reference/fields/`.
