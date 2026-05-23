# Preview

Класс: `MB\Bitrix\AdminKit\Field\Preview`.

Назначение: read-only отображение без submit.

## Доступные методы

- `badge(string $color = 'default')` — рендерит значение как цветной бейдж (`ui-label-*`).
- `link(string $target = '_blank')` — рендерит значение как ссылку, где значение поля используется как `href`.

Пример:
```php
Preview::make('Статус', 'STATUS_LABEL')->badge('success');
Preview::make('URL', 'DETAIL_URL')->link();
```

## Значения по умолчанию

- `badgeColor = null` (badge не применяется, пока не вызван `badge()`).
- `asLink = false` (рендерится как текст, пока не вызван `link()`).
- `linkTarget = "_blank"`.
- Поле всегда read-only (`isReadOnly() = true`).
