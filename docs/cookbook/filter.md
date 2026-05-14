# Как добавить фильтр

```php
public function filters(): iterable
{
    return [
        TextFilter::make('Name', 'NAME')->contains(),
        SelectFilter::make('Active', 'ACTIVE')->options(['Y' => 'Yes', 'N' => 'No'])->exact(),
    ];
}
```

Для сложной логики используйте `CallbackFilter` или добавьте ограничения в `modifyIndexParams()`.
