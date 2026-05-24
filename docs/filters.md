# Filters

`Filters` в Admin Kit описывают поля для `main.ui.filter` и преобразование пользовательского ввода в ORM-фильтр для `DataManager::getList()`.

## Когда использовать

- Когда в Resource нужно дать пользователю поиск, точное совпадение или диапазон.
- Когда фильтр должен оставаться Bitrix-native и совместимым с `bitrix:main.ui.grid`.

## Что использовать на практике

- `TextFilter` для строковых полей (`exact()`, `contains()`).
- `SelectFilter` для справочников/статусов (`options()`, `exact()`).
- `DateFilter` и `NumberFilter` для дат/чисел (`exact()`, `range()`).

## Куда идти дальше

- [Reference: Filters](user/reference/filters.md)
- [Guide: Grid](user/guides/grid.md)
- [Guide: Resource selection](user/guides/resource-selection.md)
- [Cookbook: Filter recipes](user/cookbook/filter.md)
