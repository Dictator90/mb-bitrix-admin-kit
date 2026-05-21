# Меню админки

Для интеграции созданных ресурсов и standalone-страниц в боковое меню административной панели 1С-Битрикс используется `AdminKitMenuBuilder`. Он собирает Bitrix-совместимую древовидную структуру пунктов меню, учитывая уровни прав доступа, сортировку, группировку и видимость элементов.

## Основной механизм

Построение меню осуществляется вызовом метода `getMenu()` у экземпляра `AdminKitManager` (полученного через фасад `AdminKit`):

```php
$items = \MB\Bitrix\AdminKit\AdminKit::forModule('vendor.demo')
    ->getMenu('/bitrix/admin/demo_admin.php');
```

Метод `getMenu()` принимает два параметра:
1. `baseUrl` (строка, необязательно) — URL-путь к файлу-обработчику админки. Если не передан, базовый URL определяется автоматически из `$_SERVER['REQUEST_URI']`.
2. `context` (`PermissionContext`, необязательно) — контекст прав текущего пользователя для фильтрации доступных пунктов меню.

---

## Вариант 1. Меню Bitrix-модуля

Для модулей Битрикс меню регистрируется в файле `local/modules/vendor.demo/admin/menu.php`. Файл должен возвращать массив описания меню.

Пример интеграции:

```php
<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

Loader::includeModule('vendor.demo');

return [
    'parent_menu' => 'global_menu_content', // Основной раздел (Контент, Сервисы, Настройки и т.д.)
    'section' => 'vendor_demo',             // Идентификатор вашей секции меню
    'sort' => 150,                          // Сортировка секции в общем меню
    'text' => 'Демо-модуль',                // Название секции меню
    'title' => 'Панель управления демо-модулем',
    'icon' => 'adm-menu-settings',          // Иконка секции
    'items_id' => 'vendor_demo_menu_items',
    // Динамически генерируем подпункты из зарегистрированных ресурсов и страниц:
    'items' => AdminKit::forModule('vendor.demo')->getMenu('/bitrix/admin/demo_admin.php'),
];
```

---

## Вариант 2. Локальное меню (вне модуля)

Если админка построена без создания отдельного модуля (например, в каталоге `local`), вы можете встроить пункты меню через обработчик события Битрикс `OnBuildGlobalMenu` в `local/php_interface/init.php`:

```php
<?php

use Bitrix\Main\EventManager;
use MB\Bitrix\AdminKit\AdminKit;

EventManager::getInstance()->addEventHandler(
    'main',
    'OnBuildGlobalMenu',
    function (&$aGlobalMenu, &$aModuleMenu) {
        $aModuleMenu[] = [
            'parent_menu' => 'global_menu_settings', // Встраиваем в раздел "Настройки"
            'section' => 'custom_local_admin',
            'sort' => 2000,
            'text' => 'Локальные настройки',
            'title' => 'Локальные настройки',
            'icon' => 'adm-menu-settings',
            'items_id' => 'custom_local_menu_items',
            'items' => AdminKit::fromDirectory(
                $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
                'local.admin'
            )->getMenu('/local/admin/custom_admin.php'),
        ];
    }
);
```

---

## Настройка пунктов меню в классах

Вы можете гибко настраивать отображение конкретного ресурса или страницы в меню с помощью переопределения статических и динамических методов.

### Управление видимостью

Если пункт меню не должен отображаться, переопределите метод `isVisibleInMenu()`:

```php
// В классе Resource или StandalonePage
public static function isVisibleInMenu(): bool
{
    return false; // Элемент будет скрыт из бокового меню, но доступен по прямой ссылке
}
```

### Сортировка

Сортировка пунктов меню настраивается методом `getSort()` (для страниц также поддерживается нестатический `sort()`):

```php
public static function getSort(): int
{
    return 10; // Элементы с меньшей сортировкой выводятся выше
}
```

### Иконка

Для указания иконки переопределите метод `getMenuIcon()` (для страниц также поддерживается нестатический `icon()`):

```php
public static function getMenuIcon(): string
{
    return 'adm-menu-settings'; // Можно использовать CSS-классы стандартных иконок Битрикса
}
```

### Права доступа

Пункт меню автоматически скрывается, если у текущего пользователя нет прав на его просмотр. Для ресурсов это проверяется методом `canView(PermissionContext $context)`, а для standalone-страниц — нестатическим методом `canView(PermissionContext $context)`:

```php
// Пример ограничения для standalone-страницы
use MB\Bitrix\AdminKit\Security\PermissionContext;

public function canView(PermissionContext $context): bool
{
    // Показывать пункт меню только администраторам
    return $context->isAdmin();
}
```

---

## Вложенность и Группировка

AdminKit поддерживает двухуровневые меню. Вы можете группировать ресурсы и страницы под общим родительским элементом.

Чтобы поместить ресурс или страницу внутрь родительского раздела меню, укажите ID родителя через метод `getParentMenuId()` (или `group()`):

```php
// Внутри дочернего ресурса / страницы
public static function getParentMenuId(): ?string
{
    return 'main_settings'; // ID родительского ресурса или страницы
}
```

### Как формируется родительская группа

1. Если в качестве родительского `getParentMenuId()` указан ID **существующего** (зарегистрированного) ресурса или страницы:
   - В меню сформируется раскрывающаяся группа.
   - Заголовок, иконка и сортировка группы возьмутся из класса родительского элемента.
   - Ссылка группы будет вести на родительскую страницу.
2. Если указанного родительского ID нет в реестре:
   - Сформируется группа с заголовком, равным переданной строке ID (например, `'main_settings'`).
   - Ссылка группы будет сгенерирована как ссылка на standalone-страницу с этим ID.

> [!TIP]
> Упорядочивание подпунктов внутри группы выполняется на основе их значений сортировки (`getSort()`).
