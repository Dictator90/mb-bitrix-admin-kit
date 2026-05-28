# Html

Класс: `MB\Bitrix\AdminKit\Field\Html`.

Назначение: HTML-контент с editor/fallback textarea.

## Доступные методы

- `rows(int $rows)` — задает высоту редактора/textarea.
- `disableEditor(bool $disable = true)` — отключает `bitrix:main.html.editor` и оставляет обычную textarea.

Пример:
```php
Html::make('Контент', 'HTML')
    ->rows(20)
    ->disableEditor(false);
```

## Значения по умолчанию

- `rows = 15`
- `useEditor = true`
