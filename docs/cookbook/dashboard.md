# Как сделать dashboard

```php
final class DashboardPage extends \MB\Bitrix\AdminKit\Pages\DashboardPage
{
    public static function title(): string { return 'Dashboard'; }

    protected function widgets(): iterable
    {
        return ['<div class="adminkit-widget">Products: 10</div>'];
    }
}
```

Для верстки используйте BEM-классы и не добавляйте inline styles.
