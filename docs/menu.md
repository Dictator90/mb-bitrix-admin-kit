# Меню админки v0.8.0

`AdminKitMenuBuilder` собирает массивы меню Bitrix из зарегистрированных ресурсов и standalone-страниц. Элементы сортируются по `sort()`, группируются по `group()` / `getParentMenuId()`, включают иконки, поддерживают вложенные `items` и пропускают записи, скрытые `isVisibleInMenu()` или `canView()`.
