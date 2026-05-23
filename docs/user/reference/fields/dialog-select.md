# DialogSelect

Класс: `MB\Bitrix\AdminKit\Field\DialogSelect`.

Назначение: диалог-селектор со статическими items/tabs или dynamic entities.

## Доступные методы

- `items(array $items)` — задает полный список элементов выбора.
- `tabs(array $tabs)` — задает полный список вкладок selector-а.
- `addItem(array $item)` — добавляет один элемент выбора.
- `addTab(array $tab)` — добавляет одну вкладку.
- `tabsContent(array $tabsContent)` — shortcut для одновременного описания вкладок и элементов внутри них.

Пример:
```php
DialogSelect::make('Роль', 'ROLE_ID')
    ->tabsContent([
        'base' => [
            'title' => 'Базовые',
            'items' => [
                ['id' => 'admin', 'title' => 'Администратор'],
                ['id' => 'editor', 'title' => 'Редактор'],
            ],
        ],
    ]);
```

## Значения по умолчанию

- `items = []`
- `tabs = []`
- Также наследует дефолты `EntitySelect` (`entityId = "user"`, `entityOptions = []`, `entities = []`).
