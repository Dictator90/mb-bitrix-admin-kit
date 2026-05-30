# Filters

## Что это

Фильтры описывают поля панели `main.ui.filter` и преобразуют значения из request в ORM filter для `DataManager::getList()`.

В Admin Kit фильтры задаются в `Resource::filters()` и обрабатываются на этапе построения grid query.

## Когда использовать

- текстовый поиск по названию/артикулу;
- точный фильтр по ID/статусу;
- диапазон дат и чисел;
- boolean-фильтр по `Y/N`;
- кастомное ORM-условие через callback.

## Базовый пример

```php
<?php

use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;

public function filters(): iterable
{
    return [
        TextFilter::make('Название', 'NAME')->contains(),
        SelectFilter::make('Статус', 'ACTIVE')
            ->options([
                'Y' => 'Да',
                'N' => 'Нет',
            ])
            ->exact(),
    ];
}
```

## Как Filter связан с Grid

1. `filters()` возвращает iterable фильтров ресурса.
2. Каждый фильтр отдает конфиг поля для `main.ui.filter` (`id`, `name`, `type`, `default`, `params`).
3. Значение из request проходит через `applyToOrmFilter()`.
4. В итоговый ORM-массив добавляется ключ с оператором (`=`, `%`, `>=`, `<=` и т.д.).

## Быстрый поиск в тулбаре (`searchColumns`)

Панель `main.ui.filter` имеет строку быстрого поиска (значение приходит под ключом `FIND`).
По умолчанию он ищет по строковым полям фильтра; явно ограничить/задать колонки можно методом `searchColumns()`:

```php
public function searchColumns(): array
{
    return ['NAME', 'DESCRIPTION'];
}
```

Поведение:

- значение применяется как `LIKE %value%` по указанным колонкам (несколько колонок объединяются через `OR`);
- спецсимволы LIKE (`_`, `%`) экранируются — поиск буквальный;
- если `searchColumns()` пуст, берутся строковые поля из `filters()` (тип `string`, напр. `TextFilter`).

## Доступные фильтры

| Фильтр | Когда использовать | ORM-поведение |
|---|---|---|
| `TextFilter` | Поиск по строке | `%COLUMN`, `=COLUMN`, `COLUMN%`, `%COLUMN` |
| `NumberFilter` | ID, sort, числовые диапазоны | `COLUMN`, `>=COLUMN`, `<=COLUMN`, `>COLUMN`, `<COLUMN` |
| `DateFilter` | Даты/периоды | `COLUMN` или диапазон `>=COLUMN` + `<=COLUMN` |
| `SelectFilter` | Enum/справочник | `COLUMN = value` или `IN`-подобный payload для multiple |
| `CheckboxFilter` | Булев/флаг | `COLUMN = value` |
| `CallbackFilter` | Нетиповой SQL/ORM сценарий | Логика задается callback-обработчиком |

## Общие методы

| Метод | Что делает | Когда использовать |
|---|---|---|
| `make($label, $column)` | Создает фильтр | Всегда |
| `default(true/false)` | Флаг видимости/включенности в UI по умолчанию | Когда фильтр должен быть предустановлен |
| `getFilterFieldConfig()` | Конфиг для `main.ui.filter` | Runtime, обычно не вызывается вручную |
| `prepareFieldData()` | Доп. данные (`items`, `time`) для UI | Runtime |
| `applyToOrmFilter()` | Преобразует значение в ORM filter | Runtime |

### Методы по типам

- `TextFilter`: `contains()`, `exact()`, `startsWith()`, `endsWith()`.
- `NumberFilter`: `range()`, `exact()`, `greaterThan()`, `lessThan()`.
- `DateFilter`: `range()`, `exact()`, `withTime()`.
- `SelectFilter`: `options()`, `exact()`, `multiple()`.

## Практические сценарии

### Поиск по подстроке

```php
TextFilter::make('Название', 'NAME')->contains();
```

### Точный фильтр по статусу

```php
SelectFilter::make('Статус', 'STATUS')
    ->options(['DRAFT' => 'Черновик', 'PUBLISHED' => 'Опубликован'])
    ->exact();
```

### Фильтр по периоду

```php
DateFilter::make('Дата создания', 'DATE_CREATE')->range();
```

### Число больше/меньше

```php
NumberFilter::make('Сортировка', 'SORT')->greaterThan();
```

## Связанные разделы

- [Resources](../reference/resources.md)
- [Fields overview](../../fields.md)
- [Grid guide](../guides/grid.md)
