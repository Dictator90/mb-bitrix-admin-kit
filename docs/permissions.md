# Права доступа

`PermissionContext` описывает проверки доступа с полями `userId`, `moduleId`, `resource`, `operation` и `item`.

`CrudResource` предоставляет методы `canView()`, `canCreate()`, `canUpdate()` и `canDelete()`, принимающие `PermissionContext`. Страницы проверяют права перед отрисовкой действий создания, открытием форм, сохранением данных, удалением строк и массовым удалением.
