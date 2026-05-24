# Как выбрать Resource

## Быстрый выбор

- Если у вас D7 ORM `DataManager` — выбирайте `DataManagerResource`.
- Если нужен CRUD DSL, но сохранение пишете сами — `CrudResource`.
- Если нужна нетиповая административная сущность/экран — `Resource` или standalone page.

## Decision table

| Ситуация | Базовый класс | Почему |
|---|---|---|
| Стандартный CRUD по D7 ORM | `DataManagerResource` | Есть persistence + CRUD DSL |
| Поля/фильтры/действия нужны, но save/delete нетиповые | `CrudResource` | DSL есть, persistence вы реализуете сами |
| Нестандартная секция без классического CRUD | `Resource` | Минимальная база без лишних допущений |

## DataManagerResource example

```php
final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }
}
```

## CrudResource example

```php
final class ExternalCatalogResource extends CrudResource
{
    public function indexFields(): iterable
    {
        return [/* ... */];
    }

    public function formFields(): iterable
    {
        return [/* ... */];
    }
}
```

## Resource example

```php
final class ReportsResource extends Resource
{
    protected string $title = 'Отчеты';
}
```

## Частые ошибки

- Наследоваться от `CrudResource` и ожидать стандартное ORM-сохранение.
- Использовать несуществующий namespace `MB\Bitrix\AdminKit\Pages\*`.
- Дублировать одни и те же fields в `Page` и `Resource` без необходимости.
- Пытаться делать сложный render-поток внутри `Resource` вместо страницы.

## См. также

- [Resources reference](../reference/resources.md)
- [Pages reference](../reference/pages.md)
- [First CRUD resource](../getting-started/first-crud-resource.md)
