# Reference: Filters

## Базовый класс

- `MB\Bitrix\AdminKit\Filter\Filter`

## Встроенные фильтры

- `TextFilter`
- `NumberFilter`
- `SelectFilter`
- `DateFilter`
- `CheckboxFilter`
- `CallbackFilter`

## Ограничения

- Пустые значения пропускаются, но значимые `0`, `'0'`, `false` должны сохраняться в filter payload.
