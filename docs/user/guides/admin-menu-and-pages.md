# Admin menu and pages

## Минимум для рабочей админ-страницы

1. Класс `Resource` или `StandalonePage` в discovery-пути.
2. Admin PHP-файл с `prolog_admin_before.php` / `prolog_admin_after.php` / `epilog_admin.php`.
3. Вызов `AdminKit::*()->getCurrentPage()->render()`.
4. Пункт меню Bitrix, ведущий на этот admin-файл.

## Модульный сценарий

- Меню: `local/modules/<module_id>/admin/menu.php`.
- Admin-файл: `local/modules/<module_id>/admin/<name>.php`.
- Bootstrap: `Loader::includeModule('<module_id>')`.

## Standalone сценарий

- Меню: обработчик `OnBuildGlobalMenu` в `local/php_interface/init.php`.
- Admin-файл: `local/admin/<name>.php`.
- Bootstrap: автозагрузка через `init.php` + `AdminKit::fromDirectory(...)`.
