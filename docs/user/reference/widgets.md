# Reference: Widgets

## Классы

- `Widget\AbstractWidget`
- `Widget\CountWidget`
- `Widget\ChartWidget`
- `Widget\GraphWidget` (compat alias)
- `Widget\Dashboard\DashboardRenderer`

## Общий API

- `label(string)`
- `icon(string)`
- `span(int)`
- `class()/style()/attr()`
- `getRequiredExtensions()`

## Ограничения

- `GraphWidget` — alias для совместимости; для нового кода используйте `ChartWidget`.
- Вложенные layout-компоненты внутри dashboard требуют явного `grid-column` при full width.
