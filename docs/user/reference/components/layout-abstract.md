# AbstractLayoutComponent

Класс: `MB\Bitrix\AdminKit\Component\Layout\AbstractLayoutComponent`.

Назначение: базовый контейнер для layout-компонентов.

Что дает наследникам:
- работа с дочерними элементами (`renderChildren()`);
- HTML-атрибуты/классы/стили;
- условная видимость;
- extraction полей из контейнера.

Обычно напрямую не используется, а служит базой для `Box`, `Grid`, `Column`, `Flex`, `Divider`, `LineBreak`.
