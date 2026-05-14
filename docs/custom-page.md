# CustomPage

`MB\Bitrix\AdminKit\Pages\CustomPage` — базовый класс для произвольных admin-страниц: отчётов, интеграций, превью и т.д. Расширяет `AbstractPage` и реализует `PageContract`.

---

## Минимальный пример

```php
<?php

namespace Vendor\Module\Admin;

use MB\Bitrix\AdminKit\Pages\CustomPage;

final class StatsPage extends CustomPage
{
    public static function getId(): string    { return 'stats'; }
    public static function getTitle(): string { return 'Статистика'; }

    protected function content(): string
    {
        return '<p>Здесь будет отчёт.</p>';
    }
}
```

Регистрация в admin-файле:

```php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once __DIR__ . '/../include.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
(new StatsPage())->render();
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

> `render()` — `void`, не возвращает строку. Вызывайте без `echo`.

---

## Метаданные страницы

Переопределите статические методы в своём классе:

```php
public static function getId(): string            { return 'my_stats'; }
public static function getTitle(): string         { return 'Мои отчёты'; }
public static function getSort(): int             { return 30; }
public static function getMenuIcon(): string      { return 'adm-menu-stat'; }
public static function getParentMenuId(): ?string { return 'reports'; }
```

---

## Проверка прав

```php
use MB\Bitrix\AdminKit\Security\PermissionContext;

public function canView(PermissionContext $context): bool
{
    return $context->isAdmin() || $context->hasModulePermission('vendor.module', 'W');
}
```

---

## Toolbar

```php
use MB\Bitrix\AdminKit\Manager\ToolbarAction;

protected function toolbarActions(): iterable
{
    return [
        ToolbarAction::make('Обновить')
            ->href($this->url(['refresh' => '1']))
            ->icon('--refresh'),
        '<a href="/export.php" class="adm-btn">Экспорт</a>',  // сырая HTML-кнопка
    ];
}
```

---

## Bitrix-расширения

Загружайте нужные UI extensions через свойство `$extensions` — они регистрируются через `Extension::load()` перед рендером:

```php
protected array $extensions = ['ui.buttons', 'ui.toolbar'];
```

---

## Экземплярный API (с v0.8.0)

```php
$page = new StatsPage();
$page->id();                        // 'my_stats'
$page->title();                     // 'Мои отчёты'
$page->sort();                      // 30
$page->icon();                      // 'adm-menu-stat'
$page->group();                     // 'reports'
$page->url(['foo' => 'bar']);       // '?page=my_stats&foo=bar'
$page->canView(new PermissionContext());
$page->render();                    // void — выводит HTML напрямую
```

---

## Рендер HTML

Страница выводится в AdminKit layout с BEM-классами:

```
adminkit-page
  adminkit-page__title
  adminkit-toolbar          (если есть toolbarActions)
  adminkit-page__content    (результат content())
```

---

## DashboardPage

Для страниц с виджетами, графиками и stat-карточками используйте `DashboardPage` (наследует `CustomPage`):

```php
use MB\Bitrix\AdminKit\Pages\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;

final class Overview extends DashboardPage
{
    public static function getId(): string    { return 'overview'; }
    public static function getTitle(): string { return 'Обзор'; }

    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Продукты', ProductTable::class),
        ];
    }
}
```

Подробнее — в [dashboard-page.md](dashboard-page.md).
