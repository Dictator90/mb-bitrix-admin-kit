# Pages v0.8.0

AdminKit v0.8.0 treats standalone pages as first-class admin module pages. Extend `MB\Bitrix\AdminKit\Pages\CustomPage`, `OptionsPage`, or `DashboardPage` and register it through `AdminKitManager::registerPage()` or module discovery.

Every standalone page exposes the unified instance API:

- `id(): string`
- `title(): string`
- `sort(): int`
- `icon(): ?string`
- `group(): ?string`
- `canView(PermissionContext $context): bool`
- `render()`
- `url(array $params = []): string`

The legacy static API (`getId()`, `getTitle()`, `getSort()`, `getMenuIcon()`, `getParentMenuId()`) remains supported.


## Responsibility map

- Current page resolution: `AdminKitRouter` reads request `page` / `action` parameters and returns either a standalone page, a resource page wrapper, or a not-found page.
- URL building: `UrlGenerator` owns page, resource, CRUD, action, bulk, import, and export URLs.
- Menu building: `AdminKitMenuBuilder` reads `AdminKitRegistry`, applies visibility and permission checks, groups, sorts, and returns Bitrix menu arrays.
- Toolbar rendering: standalone pages can return `ToolbarAction` objects; CRUD pages keep their existing toolbar rendering and can migrate actions incrementally.
- SidePanel behavior: `SidePanelAdapter` owns iframe parameters, slider opening JavaScript, close-after-save behavior, and grid refresh hooks.
- Asset loading: `AssetManager` owns Bitrix extension/CSS/JS registration for page-layer code.
- Rendering current page: `AdminKitRenderer` captures `render()` output for manager-driven rendering.

## Resource pages model

Resources describe the data entity. Pages describe a concrete presentation of that entity.

`Resource::pages()` is now the primary extension point for CRUD page customization. The default resource pages are:

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

Keep `indexFields()`, `formFields()`, and `detailFields()` for simple resources: they are shortcuts used by the default pages. For advanced UI behavior, register page classes instead of creating `indexResource()`, `formResource()`, `detailResource()`, `IndexResource`, `FormResource`, or `DetailResource` abstractions.

```php
final class ProductResource extends CrudResource
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

### Custom IndexPage

`IndexPage` owns grid definitions. The grid query builder, data loader, and row assembler receive definitions from the page, while the resource remains the fallback shortcut source.

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

`IndexPage` also exposes protected hooks for `rowActions()`, `bulkActions()`, default select/filter/sort/runtime values, query customization, and row mapping.

### Custom FormPage

`FormPage` takes form fields and tabs from the page. The default implementation falls back to `resource->formFields()` and `resource->formTabs()`.

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

Form pages support `mode=create` and `mode=edit`. Override `beforeSave()`, `afterSave()`, or `redirectAfterSave()` for page-specific save behavior.

### Custom DetailPage

`DetailPage` reads display fields from the page and falls back to `resource->detailFields()`.

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

### Routing parameters

The manager keeps legacy `page=<resource>&action=...` routing working, but also understands distinct resource/page parameters:

```text
admin_resource=product&admin_page=index
admin_resource=product&admin_page=form&mode=create
admin_resource=product&admin_page=form&mode=edit&id=123
admin_resource=product&admin_page=detail&id=123
```

Internally, `ResourcePageResolver` resolves `admin_page` through `Resource::pages()` and creates the page through `PageFactory`.

### FieldRenderContext

Index, form, and detail rendering now pass `FieldRenderContext` into field render methods. The context contains the field, resource, item, value, page name (`index`, `form`, or `detail`), row data, validation errors, and metadata. Existing fields that accept raw values remain backward compatible.
