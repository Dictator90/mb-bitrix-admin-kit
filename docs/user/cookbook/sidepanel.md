# SidePanel

## Задача

Открывать формы и действия в Bitrix SidePanel вместо полного перехода.

## Когда использовать

Для быстрого edit/view из Grid без потери контекста фильтра и пагинации.

## Решение

`RowAction::view()` и `RowAction::edit()` уже используют SidePanel. Для кастомного `RowAction` оставляйте URL совместимый с формой ресурса.

## Полный пример

```php
public function rowActions(): iterable
{
    return [
        RowAction::view(),
        RowAction::edit(),
    ];
}
```

## Как это работает

Адаптер вызывает `BX.SidePanel.Instance.open(...)`; после закрытия панель может триггерить перезагрузку грида.

## Что важно учесть

- SidePanel — Bitrix-native механизм; не заменяйте его кастомными модалками.
- Для fallback-режима без `IFRAME=Y` должна оставаться рабочая full-page версия.

## Связанные разделы

- [Pages](../../pages.md)
- [Actions](../../actions.md)
- [Reference: Components / SidePanel](../reference/components/sidepanel.md)
