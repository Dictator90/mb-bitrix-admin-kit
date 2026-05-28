# OptionsPage: рабочий рецепт

## 1) Создайте класс страницы

```php
final class SettingsPage extends OptionsPage
{
    public static function getId(): string { return 'vendor_demo_settings'; }
    public static function getTitle(): string { return 'Settings'; }
    protected string $moduleId = 'vendor.demo';

    public function fields(): iterable
    {
        return [Text::make('API URL', 'api_url')];
    }
}
```

## 2) Создайте admin-файл

Рендер через `AdminKit::forModule('vendor.demo')->getCurrentPage()->render()`.

## 3) Добавьте пункт меню

`AdminKit::forModule('vendor.demo')->getMenu('/bitrix/admin/vendor_demo_admin.php')`.

## 4) Откройте страницу

`/bitrix/admin/vendor_demo_admin.php?lang=ru&page=vendor_demo_settings`

## Вне модуля

Поддерживается через `AdminKit::fromDirectory(..., 'demo.admin')`; опции сохраняются в `main` module scope, поэтому используйте стабильный `scopeId`.
