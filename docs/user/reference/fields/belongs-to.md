# BelongsTo

Класс: `MB\Bitrix\AdminKit\Field\Relation\BelongsTo`.

Назначение: одиночная связь (FK).

## Доступные методы

- `relatedTable(string $class)` — задает DataManager связанной таблицы.
- `titleColumn(string $column)` — колонка, которая показывается пользователю как подпись option.
- `valueColumn(string $column)` — колонка, которая сохраняется как значение FK.
- `filter(array|Closure|null $filter)` — фильтр выборки опций.
- `orderBy(string $column, string $direction = 'ASC')` — сортировка опций.
- `emptyOption(string $label = '')` — добавляет пустой вариант в начало списка.
- `options(Closure $callback)` — полностью кастомный источник опций.
- `asSelect()` — рендер как `<select>`.
- `asRadio()` — рендер как список radio.
- `asLink()` — рендер read-only preview как ссылка/текст.

Пример:
```php
BelongsTo::make('Категория', 'CATEGORY_ID', CategoryTable::class)
    ->titleColumn('NAME')
    ->emptyOption('— выберите —');
```

## Значения по умолчанию

Переопределения относительно `RelationField`:

- `readonly = false`
- `titleColumn = "NAME"`
- `valueColumn = "ID"`
- `emptyLabel = ""` (пустая опция отключена, пока не вызван `emptyOption()`).
- `filter = []`
- `order = []`
- `renderMode = "select"`
- `optionsCallback = null`
