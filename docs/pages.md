# Страницы v0.8.0

В AdminKit v0.8.0 standalone-страницы — полноценные страницы admin-модуля. Расширяйте `MB\Bitrix\AdminKit\Pages\CustomPage`, `OptionsPage` или `DashboardPage` и регистрируйте через `AdminKitManager::registerPage()` или discovery модуля.

Каждая standalone-страница предоставляет единый instance API:

- `id(): string`
- `title(): string`
- `sort(): int`
- `icon(): ?string`
- `group(): ?string`
- `canView(PermissionContext $context): bool`
- `render()`
- `url(array $params = []): string`

Устаревший static API (`getId()`, `getTitle()`, `getSort()`, `getMenuIcon()`, `getParentMenuId()`) по-прежнему поддерживается.


## Карта ответственности

- Разрешение текущей страницы: `AdminKitRouter` читает параметры запроса `page` / `action` и возвращает standalone-страницу, обёртку resource page или страницу not-found.
- Построение URL: `UrlGenerator` отвечает за URL страниц, ресурсов, CRUD, действий, bulk, import и export.
- Построение меню: `AdminKitMenuBuilder` читает `AdminKitRegistry`, применяет видимость и проверки прав, группирует, сортирует и возвращает массивы меню Bitrix.
- Рендер toolbar: standalone-страницы могут возвращать объекты `ToolbarAction`; CRUD-страницы сохраняют существующий рендер toolbar и могут переносить действия постепенно.
- Поведение SidePanel: `SidePanelAdapter` владеет параметрами iframe, JS открытия слайдера, закрытием после сохранения и хуками обновления грида.
- Загрузка ассетов: `AssetManager` регистрирует расширения/CSS/JS Bitrix для page-слоя.
- Рендер текущей страницы: `AdminKitRenderer` захватывает вывод `render()` для manager-driven рендера.

## Модель UI-слоя

- Поля описывают данные/поведение и реализуют `Contracts\Field\FieldContract`.
- `FieldRowRenderer` — единственное место рендера разметки Bitrix `ui-form-row`.
- `ComponentContract` — только рендер; поведение контейнеров в `LayoutComponentContract`.
- Layout-компоненты (`Box`, `Grid`, `Column`, `Tabs`) рендерят дочерние элементы через `ChildrenRenderer`.
- Виджеты — листовые компоненты (`AbstractWidget` больше не наследует layout-контейнеры); композиция dashboard идёт через `DashboardRenderer`.

## Модель resource pages

Ресурсы описывают сущность данных. Страницы — конкретное представление этой сущности.

`Resource::pages()` — основная точка расширения для кастомизации CRUD-страниц. Страницы ресурса по умолчанию:

```php
public function pages(): iterable
{
    return [
        IndexPage::class,
        FormPage::class,
        DetailPage::class,
    ];
}
```

Оставляйте `indexFields()`, `formFields()` и `detailFields()` для простых ресурсов: это shortcuts, которые используют страницы по умолчанию. Для сложного UI регистрируйте классы страниц вместо `indexResource()`, `formResource()`, `detailResource()`, `IndexResource`, `FormResource` или `DetailResource`.

```php
final class ProductResource extends DataManagerResource
{
    public function pages(): iterable
    {
        return [
            ProductIndexPage::class,
            ProductFormPage::class,
            ProductDetailPage::class,
        ];
    }
}
```

### Кастомный IndexPage

`IndexPage` владеет определениями грида. Grid query builder, data loader и row assembler получают определения со страницы, а ресурс остаётся fallback-источником shortcut.

```php
final class ProductIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Название', 'NAME'),
            Text::make('Раздел', 'SECTION_NAME'),
            Text::make('Остаток', 'QUANTITY'),
            Switcher::make('Активность', 'ACTIVE'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME'),
            SelectFilter::make('Активность', 'ACTIVE')->options([
                'Y' => 'Да',
                'N' => 'Нет',
            ]),
        ];
    }

    protected function defaultSort(): array
    {
        return ['ID' => 'DESC'];
    }
}
```

`IndexPage` также предоставляет protected-хуки для `rowActions()`, `bulkActions()`, значений select/filter/sort/runtime по умолчанию, кастомизации запроса и маппинга строк.

### Кастомный FormPage

`FormPage` берёт поля формы и вкладки со страницы. Реализация по умолчанию откатывается к `resource->formFields()` и `resource->formTabs()`.

```php
final class ProductFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
            Select::make('Тип', 'TYPE')->options([
                'simple' => 'Обычный',
                'service' => 'Услуга',
            ]),
            Switcher::make('Активность', 'ACTIVE'),
        ];
    }

    protected function afterSave(FormData $data, DbOperationContext $context, mixed $savedId): void
    {
        // custom logic
    }
}
```

Form pages поддерживают `mode=create` и `mode=edit`. Переопределите `beforeSave()`, `afterSave()` или `redirectAfterSave()` для поведения сохранения на странице.

### Кастомный DetailPage

`DetailPage` читает поля отображения со страницы и откатывается к `resource->detailFields()`.

```php
final class ProductDetailPage extends DetailPage
{
    protected function fields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Название', 'NAME'),
            Text::make('Дата создания', 'DATE_CREATE'),
        ];
    }
}
```

### Параметры маршрутизации

Менеджер сохраняет работу legacy-маршрутизации `page=<resource>&action=...`, но также понимает отдельные параметры resource/page:

```text
admin_resource=product&admin_page=index
admin_resource=product&admin_page=form&mode=create
admin_resource=product&admin_page=form&mode=edit&id=123
admin_resource=product&admin_page=detail&id=123
```

Внутри `ResourcePageResolver` разрешает `admin_page` через `Resource::pages()` и создаёт страницу через `PageFactory`.

### FieldRenderContext

Рендер index, form и detail передаёт в методы полей `FieldRenderContext`. Контекст содержит поле, ресурс, элемент, значение, имя страницы (`index`, `form` или `detail`), данные строки, ошибки валидации и метаданные. Существующие поля, принимающие «сырые» значения, остаются обратно совместимыми.

## Безопасность

| Страница | Проверки |
|------|--------|
| `IndexPage` | `canView` перед grid/export; `canUpdate`/`canDelete` для inline/bulk; CSRF на POST-действиях |
| `FormPage` | `canView`; `canCreate` (create) / `canUpdate` (edit); sessid при сохранении; async save → JSON |
| `DetailPage` | `canView` перед рендером записи |
| `Pages\OptionsPage` | `canView`; неверный sessid блокирует `Option::set` (AJAX → JSON, обычный POST → alert) |

## Экспорт на index

CSV-экспорт доступен, когда ресурс регистрирует export action и у пользователя есть `canView`. Import toolbar/flow на `IndexPage` **временно удалён**; экспорт использует `ExportAction` с pre-flight `maxExportRows()`.

## Standalone-страницы и страницы ресурса

| Тип | Классы | Меню |
|------|---------|------|
| Resource CRUD | `Page\IndexPage`, `FormPage`, `DetailPage` (через `Resource::pages()`) | Под пунктом меню ресурса |
| Standalone | `Pages\OptionsPage`, `DashboardPage`, `CustomPage` | Регистрируются/discovered отдельно |

Не регистрируйте подклассы resource page как standalone-пункты меню, если они явно не реализуют standalone page API.
