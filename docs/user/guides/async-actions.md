# AsyncAction (AJAX endpoint)

## Когда использовать

Когда нужно действие с JSON-ответом без перезагрузки страницы.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\Action\AsyncAction;

final class ReindexAction extends AsyncAction
{
    public function __construct()
    {
        parent::__construct('reindex', 'Переиндексация');
    }

    public function handle(array $data): array
    {
        // ваша доменная логика
        return ['queued' => true, 'task' => 123];
    }
}
```

Dispatch:

```php
$action->dispatch($request);
```

## JSON-flow

- Успех: `{"status":"success","data":{...}}`
- Ошибка: `{"status":"error","message":"..."}`
- CSRF: `dispatch()` требует валидный `sessid`.

## Ограничения

- Не возвращайте HTML в `handle()`: только массив данных.
- Исключения в `handle()` превращаются в error JSON.

## См. также

- [Reference: Actions](../reference/actions.md)
- [Guides: Permissions](permissions.md)
