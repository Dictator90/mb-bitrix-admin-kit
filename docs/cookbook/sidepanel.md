# Как открыть форму в SidePanel

```php
public function useSidePanel(): bool
{
    return true;
}

public function sidePanelWidth(): int
{
    return 960;
}
```

SidePanel применяется к create/edit/detail URL и row actions, но форма остается доступной в full-page режиме.
