# Upgrade notes and deprecation policy

## Unreleased (features branch) — merge prep

### Import temporarily disabled

- Index-page import toolbar, SidePanel import wizard, and `action=import` handling are removed from `IndexPage`.
- Export remains CSV-first via `ExportAction` / `Export\CsvExporter`.
- Library classes under `Import\*` are unchanged; do not document or ship import UI until it is re-enabled.

### Resource architecture refactor

- New hierarchy: `Resource` (core) -> `CrudResource` (DSL) -> `DataManagerResource` (ORM).
- **Breaking change**: ORM-backed resources must now extend `DataManagerResource` instead of `CrudResource`.
- `CrudResource` no longer includes Bitrix ORM persistence logic by default.
- Legacy resources extending `Resource` directly can keep ORM logic by using `HasDataManager` and `HasDataManagerPersistence` concerns.
- `ResourceContract` is now a legacy aggregate; use narrow contracts from `Contracts\Resource\*` in new code.
- `RelationField` is now `readonly(true)` by default.

### Security hardening

- POST saves and options updates require valid `check_bitrix_sessid()`.
- `IndexPage`, `FormPage`, and `DetailPage` enforce Resource permission methods before render/save/export.
- Export requires `canView` and respects `maxExportRows()` (default `5000`).

### Resource / pages

- New ORM sections: extend `DataManagerResource`. Legacy code may keep `extends Resource` (with ORM traits) or migrate `extends CrudResource` -> `extends DataManagerResource`.
- Customize UI via `Resource::pages()` and page subclasses; `indexFields()` / `formFields()` / `detailFields()` remain shortcuts.
- Standalone: `Pages\OptionsPage` (settings), `Pages\DashboardPage` (widgets). Deprecated: `Page\OptionsPage` wrapper.

### Grid and JS

- ORM query building: `GridQueryBuilder` only; loading: `GridDataLoader`; Bitrix UI: `Grid` + adapters.
- Form/options client scripts live in `mb.admin.kit` extension (`MB.AdminKit.Form`, `.Visibility`, `.OptionsPage`).
- Tabs and DialogSelector runtime also live in `mb.admin.kit` (removed `mb.ui.tabs` / `mb.ui.dialog-selector`).

**Before:**

```js
BX.Runtime.loadExtension('mb.ui.tabs').then((m) => { /* ... */ });
BX.Runtime.loadExtension('mb.ui.dialog-selector').then(() => {
    new MB.UI.DialogSelector.DialogSelector({ /* ... */ }).render();
});
```

**After:**

```js
BX.Runtime.loadExtension('mb.admin.kit').then((kit) => {
    kit.Tabs.initTabs({ containerId: '...', items: [], bodies: [], remember: true });
    kit.DialogSelector.initDialogSelector({ targetId: '...', name: 'FIELD', dialog: {} });
});
```

Or rely on AdminKit PHP renderers (`data-adminkit-tabs` / `data-adminkit-dialog-selector`) and call `kit.Tabs.initAll()` / `kit.DialogSelector.initAll()` after the extension loads.

### Contracts

- `ResourceContract` is aggregate; narrow contracts (`IndexResourceContract`, `ExportResourceContract`, etc.) are for internal typing.

---

## Что изменилось в v0.10.0 — Widget System

### Новое

**`DashboardPage` — полноценная система виджетов.**
`widgets()` теперь принимает не только сырые строки, но и любые `ComponentContract` и виджеты.

**`AbstractWidget`** (`src/Widget/AbstractWidget.php`)
Базовый класс для виджетов. Наследует `AbstractLayoutComponent`, поэтому совместим с `Grid`, `Column`, `Box`. Методы: `span()`, `label()`, `icon()`, `getRequiredExtensions()`. Реализуйте `renderWidget(): string`.

**`CountWidget`** (`src/Widget/CountWidget.php`)
Stat-карточка с ORM-счётчиком. `make(label, tableClass)`, `filter()`, `color()`, `icon()`, `href()`, `value(\Closure)`, `span()`.

**`GraphWidget`** (`src/Widget/GraphWidget.php`)
График на Chart.js 4 (CDN, без зависимостей от Bitrix). Типы: `'bar'` (по умолчанию), `'line'`, `'pie'`, `'doughnut'`. Методы: `horizontal()`, `categoryField()`, `valueField()`, `barColor()`, `height()`, `config()`, `data()`, `dataCallback()`, `span()`.

**`Alert::html()`** (`src/Component/Alert.php`)
Новый метод `->html(bool $raw = true)`: отключает `htmlspecialcharsbx()` и позволяет передавать сырой HTML (ссылки, теги). Используйте только с контролируемым кодом, не с пользовательским вводом.

### Breaking change: `CustomPage::render()` теперь `void`

`CustomPage::render()` изменён с `string` на `void` — метод напрямую делает `echo` вместо return. Это приводит к единообразию с `OptionsPage` и стандартным Bitrix-паттерном:

```php
// admin-файл — НЕ нужно echo:
(new MyPage())->render();

// Если вы перехватывали return — перейдите на ob_start():
ob_start();
(new MyPage())->render();
$html = ob_get_clean();
```

PHPUnit-тесты для страниц нужно обновить аналогично.

### GraphWidget: переход с AmCharts на Chart.js

Если вы использовали `->config([...])` с AmCharts-специфичной структурой (`graphs`, `categoryAxis`, `valueAxes` и т.д.), замените на Chart.js API:

| AmCharts (старый) | Chart.js (новый) |
|---|---|
| `->config(['categoryField' => 'x'])` | `->categoryField('x')` |
| `->config(['graphs' => [['valueField' => 'y']]])` | `->valueField('y')` |
| `->config(['rotate' => true])` | `->horizontal()` |
| `make('...', 'serial')` | `make('...', 'bar')` — `'serial'` принимается как alias |

---

## Что изменилось в v0.9.0

- Переписан README под быстрый старт нового разработчика.
- Добавлены `docs/quick-start.md`, cookbook, architecture docs и support-packages docs.
- Добавлен реалистичный demo-module.
- Стабилизированы команды разработки: `composer test`, `composer analyse`, `composer cs-fix`.
- Добавлены PHPStan level 6, php-cs-fixer config и GitHub Actions.

## Deprecated

v0.9.0 не удаляет публичные методы и не вводит намеренных breaking rename. Старые алиасы selector-полей и page API сохраняются.

## Что будет удалено в v1.0

Кандидаты на удаление будут объявлены заранее и помечены `@deprecated`. Удаление публичного метода без deprecation запрещено.

## Как переходить на новые методы

- URL строить через `Support\UrlGenerator`, а не ручную конкатенацию.
- Новые CRUD-разделы наследовать от `CrudResource`.
- Для настроек использовать `Pages\OptionsPage`.
- Для произвольных страниц использовать `Pages\CustomPage`/`DashboardPage`.
- Для helper-логики ядра использовать `AdminCollection`, `AdminString`, `AdminCondition`.

## Deprecation policy

- Не удалять публичные методы без deprecation-периода.
- Deprecated methods помечать phpdoc `@deprecated` с версией и заменой.
- В `docs/upgrade.md` указывать замену и пример миграции.
- Backward-compatible alias оставлять до ближайшей major-версии, если нет критической причины удалить раньше.
