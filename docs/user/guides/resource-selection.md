# Как выбрать базовый класс ресурса

## Когда использовать

Перед стартом новой админ-сущности или при миграции legacy-ресурса.

## Минимальный пример

- `DataManagerResource`: ORM CRUD с формами и `EntityObject` persistence.
- `CrudResource`: DSL страниц/полей/фильтров/действий без встроенного ORM persistence.
- `Resource`: максимальная совместимость для legacy и кастомных сценариев.

```php
final class ProductResource extends DataManagerResource {}
```

## Ограничения

- Для D7 ORM CRUD не используйте `CrudResource` как конечную базу.
- `DataManagerResource` не поддерживает array-mode сохранения ORM форм.

## См. также

- [First CRUD](../getting-started/first-crud-resource.md)
- [Reference: Resources](../reference/resources.md)
