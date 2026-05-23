# Component (базовые принципы)

## Базовые интерфейсы

- `ComponentContract` — любой UI-компонент, который умеет `render()`.
- `LayoutComponentContract` — контейнер для детей (поля/компоненты).

## Общие возможности layout-компонентов

Для классов, наследующих `AbstractLayoutComponent`, обычно доступны:
- `class(...)` — CSS-классы
- `style($name, $value)` — inline style
- `attr($name, $value)` — произвольные HTML-атрибуты
- `visible(...)` — условная видимость
- `withItem(...)`, `withPageType(...)` — контекст рендера

## Встраивание в страницы

- В `OptionsPage`/`CustomPage`/`DashboardPage` компоненты можно возвращать вместе с полями.
- В layout-контейнерах (`Box`, `Grid`, `Tabs`) можно смешивать поля и дочерние компоненты.

## Ограничения

- Компонента `Layout\Collapse` в текущем API нет.
- Inline styles используйте только когда нет практичной альтернативы.
