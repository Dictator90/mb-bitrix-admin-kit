# Как добавить row action

```php
public function rowActions(): iterable
{
    return [
        RowAction::edit(),
        RowAction::view(),
        RowAction::delete(),
    ];
}
```

`RowAction::delete()` добавляет confirm и CSRF-url. Edit/view могут открываться в SidePanel.
