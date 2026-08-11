# IblockSectionElementSelect

Класс: `MB\Bitrix\AdminKit\Field\IblockSectionElementSelect`.

Назначение: выбор разделов и элементов одного инфоблока в общем упорядоченном списке. В отличие от [IblockElementSelect](iblock-element-select.md) и [IblockSectionSelect](iblock-section-select.md) открывает один диалог `ui.entity-selector` с двумя вкладками — «Разделы» и «Элементы» — и позволяет смешивать сущности разных типов, задавая их порядок перетаскиванием чипсов.

## Доступные методы

- `iblockId(int $iblockId)` — инфоблок, из которого грузятся разделы и элементы.
- `tabsTitles(string $sections, string $elements)` — заголовки вкладок диалога.
- `IblockSectionElementSelect::decode(mixed $value): array` — статический разбор сохранённого значения в типизированный список `[['type' => 'section'|'element', 'id' => int], …]` в порядке, заданном редактором.

Пример:
```php
IblockSectionElementSelect::make('Услуги', 'HOME_SERVICES', $servicesIblockId)
    ->tabsTitles('Разделы услуг', 'Услуги');
```

Чтение на стороне потребителя:
```php
foreach (IblockSectionElementSelect::decode($storedValue) as $item) {
    if ($item['type'] === 'section') {
        // раздел $item['id']
    } else {
        // элемент $item['id']
    }
}
```

## Формат значения

Значения хранятся с префиксом типа, поэтому раздел и элемент с одинаковым ID не конфликтуют, а тип восстанавливается без обращения к БД:

| Значение | Смысл |
|---|---|
| `s:<id>` | раздел |
| `e:<id>` | элемент |
| `<id>` | элемент — обратная совместимость со старым форматом поля |

## Значения по умолчанию

- `iblockId = 0` — без инфоблока список пуст.
- Поле создаётся `multiple()` и `sortable()`.
- Заголовки вкладок — «Разделы» и «Элементы».

## Ограничения

Разделы и элементы грузятся статически, по одному запросу на каждый тип, без постраничного догружения. Поле рассчитано на небольшие инфоблоки (услуги, направления); для крупных справочников нужен динамический провайдер — см. [entity-select](entity-select.md).
