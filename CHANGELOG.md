# Changelog

## [0.1.9]

### Added
- `Field\YandexMap` — интерактивный редактор меток на Яндекс.Картах. Значение — единый JSON (центр/зум + список цветных меток с заголовком и описанием). Список меток под картой остаётся источником истины и полностью редактируем, даже если скрипт карт не загрузился или не задан API-ключ (карта — прогрессивное улучшение). Настройки: `center()`, `zoom()`, `apiKey()`, `height()`.
- `Field\Email` теперь повторяемый: `multiple()` включает список адресов с добавлением/удалением (значение — плоский массив строк), как у `Field\Phone`.
- Перетаскивание для смены порядка. Для повторяемых скалярных полей (`Phone`, `Email` и др. на трейте `RepeatableScalar`) появилась опция `sortable()` с drag-and-drop строк. Для `Field\EntitySelect` в множественном режиме `sortable()` включает перетаскивание выбранных чипсов; порядок скрытых инпутов синхронизируется с DOM.
- `Field\Image` — миниатюры изображений в гриде «из коробки» через `Grid\Row\Assembler\ImageAssembler` (поддержка нескольких значений: массив, id через запятую, файловые массивы). Размер миниатюры настраивается `gridThumbSize(int $width, ?int $height)` (по умолчанию 40×40).
- Поля можно объявлять без метки: `label` во всех конструкторах полей и в `make()` теперь необязателен. При пустой метке `column` обязателен (иначе `InvalidArgumentException`); строка формы рендерится без ярлыка (`adminkit-form-row--no-label`).

### Changed
- `Field\Phone` — маска стала страно-независимой и поддерживает **несколько масок** с авто-выбором по коду страны (`mask([Phone::MASK_RU, Phone::MASK_US])`). Ввод ограничивается ёмкостью маски (`maxlength` + обрезка хвоста). Значение приводится к каноничному виду при хранении (`+` и только цифры, напр. `+79999999999`), на вывод снова форматируется маской. **BC:** константа `Phone::DEFAULT_MASK` заменена на `Phone::MASK_RU` / `Phone::MASK_US` / `Phone::DEFAULT_MASKS`.
- `Field\Json` — ширина колонок в гриде берётся из `getColumnWidth()` каждого сабполя (`minmax(0, 1fr)` по умолчанию); `label` необязателен.

### Fixed
- `Field\File` / `Field\Image` — uploads (especially video) intermittently failed with a bogus **"Unexpected server response"** while the file actually reached the server. Root cause is a Bitrix core bug in `BX.UI.FileInput.replaceInput` (`bitrix/js/main/core/core_fileinput.js`): after a successful upload its DOM-cleanup loop walks into a sibling node without a `name` and throws `Cannot read properties of undefined (reading 'indexOf')`, which the uploader catches and mislabels. The `File` field now emits a one-time client patch that reinstalls a guarded copy of `replaceInput` (`typeof input.name === 'string'` in the loop + null-input bailout). No Bitrix core files are modified.

## [0.1.8]

### Fixed
- `Field\File` / `Field\Image` on UserField "file" columns (Highload-block `UF_*` files, and other UF-enabled DataManagers) now persist correctly. The ORM EntityObject `save()` path does not process UF file fields (only the DataManager `add()`/`update()` API does), and the UF save layer rejects a foreign pre-saved file id. Such columns are now detected and written via the DataManager API with a file array in `Relation\EntityObjectFormSaver`. Previously the file id silently reset to `0`.

### Added
- `Support\UserFieldFileColumns` — resolves which DataManager columns are UserField `file` fields (via `getUfId()` or Highload-block entity lookup), cached per class.
- `Field\File::ormExpectsFileArray()` — makes the field hand the ORM a file array instead of a pre-saved id (set automatically by the persistence layer for UF file columns).
- `Field\Image` — renders image thumbnails in the grid out of the box via `Grid\Row\Assembler\ImageAssembler` (multiple values supported: array, comma-separated ids, or file arrays). Thumbnail size is configurable with `gridThumbSize(int $width, ?int $height)` (default 40×40).

### Changed
- `Field\Image::getGridColumnType()` now returns `text` instead of `file`. `main.ui.grid` has no native `file`/`image` column type — the value had no rendering effect; image display is handled by the cell HTML (assembler).

## [0.1.7]

### Changed
- `Field\Html` renamed to `Field\HtmlEditor`; `Html` kept as deprecated alias.
- `Field\Slug` — `dependsOn('COLUMN')` without callback now registers the column as slug source (same as `from()`).
- `Support\AdminString::slug()` — uses Bitrix `CUtil::translit()` for Cyrillic and other non-Latin characters.

### Fixed
- Reactive form updates pass `$formData` into `renderFormField()` for dependent fields (OptionsPage and FormPage).
- `Field\HtmlEditor` — replaced non-existent `bitrix:main.html.editor` component with native Bitrix `CHTMLEditor` (`fileman` module / `BXHtmlEditor`). Falls back to textarea when `fileman` is unavailable or editor is disabled.

### Added
- `Field\HtmlEditor` — extends `Textarea`, uses `HasHtmlEditor` trait; CHTMLEditor options and rendering live in the field (no separate renderer/options class).
- `examples/component-showcase/AdminKitTestPage.php` — portable `OptionsPage` showcase for fields and UI/layout components (`KIT_TEST_*` option keys, six tabs including `visibleWhen` demo). Requires explicit `$moduleId` on the page class (e.g. `vendor.demo`).

## [0.1.6]

### Added
- Тулбар index-страницы: `ToolbarAction` получил выпадающее меню (`items()`/`addItem()`), split-режим (`split()`), стиль (`color()`, `icon()`), произвольный клик (`onclick()`), открытие в слайдере (`sidePanel()`) и позиционирование (`location()` — значения `Bitrix\UI\Toolbar\ButtonLocation`). Пункты меню рендерятся через `Button::setMenu()`, split — через `Split\Button`.
- `ToolbarAction` — расширенные опции кнопки: `counter()` (бейдж), `size()` (`Bitrix\UI\Buttons\Size`), `disabled()` (`State::DISABLED`), `round()`, `collapsedIcon()` (адаптивное сворачивание). Прокидываются в `Bitrix\UI\Buttons\Button` через `ToolbarRenderer::baseButtonOptions()`.
- Фичи тулбара как хуки ресурса (`ResourceToolbarContract` / `HasResourceToolbar`), маппятся на фасад `Toolbar` в `ToolbarRenderer`: `toolbarTitle()`, `toolbarEditableTitle()`, `toolbarFavoriteStar()`, `toolbarCopyLink()`, `toolbarBeforeTitleHtml()`, `toolbarAfterTitleHtml()`, `toolbarUnderTitleHtml()`, `toolbarRightHtml()`. Все по умолчанию выключены. `toolbarEditableTitle()`/`toolbarFavoriteStar()` — только UI (без серверной персистентности).
- Настройки грида как хуки ресурса (`ResourceGridContract` / `HasResourceGrid`) через value object `Grid\GridSettings`, маппятся на `bitrix:main.ui.grid` в `BitrixGridAdapter`: `allowColumnsSort()`, `allowColumnsResize()`, `allowHorizontalScroll()`, `allowRowsSort()`, `allowContextMenu()`, `pinHeader()`, `stickedColumns()`, `showGridSettingsMenu()`, `enableFieldsSearch()`, `showSelectedCounter()`, `showTotalCounter()`, `useAjax()`, `pageSizes()`, `gridEmptyMessage()`, `gridAggregates()`, `gridFooter()`, `tileMode()`/`tileSize()`/`tileItemJsClass()`/`rowLayout()`.
- Опции grid-колонки в полях (`HasFieldGridColumn`): `width()`, `align()`, `color()`, `sticked()` — добавляются в config колонки `main.ui.grid` только когда заданы.
- Drag-сортировка строк грида с сохранением порядка: хуки `Resource::allowRowsSort()` + `sortField()` + `reorder()`; JS-расширение `MB.AdminKit.GridRowSort` (слушает `Grid::rowMoved`, POST `action=rowsort`) и серверный `IndexRowSortHandler`. Дефолтный `reorder()` пишет инкрементальные значения в `sortField()` через DataManager. Нативный `ALLOW_ROWS_SORT_INSTANT_SAVE` отключён (порядок персистится своим обработчиком). Для сгруппированного грида поддержан перенос строки между группами: `IndexRowSortHandler` извлекает целевую группу из `group:`-маркеров payload и передаёт в `reorder($orderedIds, $groupByItemId, $groupField)`, обновляя и порядок, и FK группировки. Шаг инкремента SORT настраивается хуком `Resource::sortStep()` (default 100).
- `Resource::showCreateButton()` (скрыть стандартную кнопку «Создать») и `Resource::createButtonLabel()` (своя подпись).
- `Resource::exportEnabled()` — единый выключатель экспорта (default `false`): управляет кнопкой экспорта в тулбаре, действием «Экспорт выбранных» в групповой панели и эндпоинтами `action=export`/`export_selected`.
- `Resource::searchColumns()` — явный список колонок для строки быстрого поиска тулбара (`FIND`); при пустом значении используются строковые поля `filters()`.
- `Resource::showPagination()` — при `false` все записи выводятся одной страницей, нижняя панель навигации скрывается; добавлены `Grid::showAllRecords()` / `Grid::setShowNavigation()`.
- - Added fluent settings methods on `Field\File` for configuring Bitrix native `FileInput`: `canUpload()`, `canEdit()`, `canDelete()`, `useCloud()`, `medialib()`, `fileDialog()`, `maxCount()`, `uploadType()`, and `description()`.

### Changed
- Relation-поля: `asEntitySelector()` переименован в `asDialogSelector()` (старое имя — `@deprecated` алиас) и теперь действительно рендерит Dialog Selector на базе Bitrix `ui.entity-selector` (компонент `MB.AdminKit.DialogSelector`) вместо прежней заглушки-warning. Поддержано для `BelongsTo` (одиночный) и `BelongsToMany` (множественный); опции связи (`loadOptions`) передаются как статические элементы диалога.
- Экспорт по умолчанию **выключен** (раньше кнопка экспорта и `export_selected` добавлялись всегда). Включается флагом `exportEnabled()`; `allowExportByFilter()/allowExportAll()/maxExportRows()` уточняют политику при включённом экспорте.
- `Resource::toolbarActions()` по умолчанию возвращает `[]` (раньше `['export']`); экспорт отвязан от `toolbarActions()`.
- Параметры `bitrix:main.ui.grid` `ALLOW_COLUMNS_SORT/RESIZE`, `ALLOW_HORIZONTAL_SCROLL`, `ALLOW_ROWS_SORT`, `AJAX_MODE` теперь конфигурируемы через хуки ресурса (раньше были зашиты в `BitrixGridAdapter`). Поведение по умолчанию не изменилось.
- Upgraded `Field\File` to render using Bitrix's native `Bitrix\Main\UI\FileInput` control.
- Upgraded `Field\Image` to render using Bitrix's native `bitrix:ui.image.input` component, with defaults suitable for image inputs.
- Implemented robust, idempotent file deletion and new file upload persistence inside `File::normalize()`, ensuring it works uniformly for both `CrudResource` and `DataManagerResource` pipelines.


### Fixed
- Строка быстрого поиска тулбара (`FIND`) теперь применяется к ORM-запросу (`LIKE %value%` по `searchColumns()`); ранее игнорировалась — искал только явный фильтр.
- `GridDataLoader::makeContext()`: устранён `TypeError` (`$limit` = `null`) при выборе «Показать все» — лимит берётся как `maxPageSize()`.
- Fixed `Field\Image` rendering in AJAX/SidePanel context and resolved the `onUploaderIsInited` race condition: if the uploader is initialized before `BX.UI.ImageInput` registers its listener, the container remains hidden with `display: none`. Added explicit JS/CSS preloading and an inline helper initialization script.
- Fixed `Field\File` (and `Field\Image`) to properly handle multiple files saving by adding support for temporary string paths (resolved via `\CFile::MakeFileArray`) and standard multipart `$_FILES` uploads fallback.
- Fixed `Field\File` (and `Field\Image`) to support parsing and loading stored multiple file values represented as JSON array strings, serialized PHP arrays, or comma-separated lists of IDs, preventing display issues and file loss on save. Multiple file values on OptionsPage are now stored using standard PHP serialize/unserialize for native Bitrix compatibility, while maintaining backward-compatible reading of older JSON array strings.
- Fixed `resolveValue` in `HasFieldValue` trait to correctly handle multiple field resolved values (indexed array of IDs) and prevent them from being mistaken for row data arrays (which was resolving to `null` and failing to display).


### Documentation
- Документация интеграции переработана в end-to-end формате: добавлены user guides `module-integration.md`, `standalone-integration.md`, `admin-menu-and-pages.md`; обновлены `docs/installation.md`, `docs/quick-start.md`, `docs/options-page.md`, cookbook `first-crud.md`/`options-page.md`, карта docs и README-ссылки. Исправлена сигнатура `DataManagerResource::dataManagerClass()` в cookbook (не static), дубли cookbook переведены в bridge-файлы к каноническим рецептам (`add-bulk-action`, `add-row-action`, `add-filter`, `custom-field`).
- Этап 7 документации: выполнен финальный audit навигации и cookbook, добавлен `docs/quality-checklist.md`, обновлены карта документации (`docs/README.md`) и cookbook-рецепты (`docs/user/cookbook/README.md`, `filter.md`, `row-action.md`, `bulk-action.md`) с единым шаблоном «Задача/Решение/Полный пример/Что важно учесть/Связанные разделы», уточнены Bitrix-native и bulk-security акценты без изменений runtime-кода.
- Этап 6 (polishing/link audit): добавлены bridge-страницы `docs/filters.md` и `docs/import-export.md`, обновлены обзорные ссылки в `README.md` и `docs/README.md`, подтверждена отсутствие битых markdown-ссылок и сохранена актуальная формулировка статуса import/export (CSV-first, import UI отключён на `IndexPage`).
- Docs hardening fix: дополнительно выверены README и обзорные ссылки документации (`docs/actions.md`, `docs/options-page.md`, `docs/dashboard-page.md`, `docs/user/guides/import-export.md`) без изменения runtime-кода; подтверждена валидность минимального `DataManagerResource` примера по текущему API.
- Этап 5 документации: исправлены stub/схлопнутые markdown-страницы (`docs/options-page.md`, `docs/dashboard-page.md`, cookbook import/export/options/dashboard), выровнены относительные ссылки без лишнего префикса `docs/`, обновлены PHP-примеры под фактический API (`OptionsPage`, `DashboardPage`, `ImportAction`, export-политики), и синхронизирован статус import/export (CSV export UI включен, import UI на index временно отключен, XLSX не поддерживается).

## [0.1.5]

### Added

- Added unified field condition engine: `FieldConditionContext`, closure-aware `required()/readonly()/visible()/canSee()`, universal `when()` with optional `dependsOn`, DataPipeline conditional application, and reactive dependency input listeners for text-like inputs.
- Added `Field\Slug` with MoonShine-like source-based slug generation (`from()`), configurable separator (`separator()`), and dependency-driven reactive updates.
- `DataManagerResource` / `DataManagerResourceContract::getEntity()` — ORM Entity из `dataManagerClass()` (`DataManager::getEntity()`).
- `DataManagerResource` всегда сохраняет формы через Bitrix EntityObject: `queryObject()`, `findObject()`, `newObject()`, `EntityObjectFormSaver` / `RelationObjectMutator`, `$entityObject->save()`.
- `FormPage` маршрутизирует `DataManagerResource` в object-graph flow, `CrudResource` с ручной persistence — в `createItemResult()` / `updateItemResult()`.
- Явные хелперы `BelongsToMany::isOrmRelationMode()` / `isStoredAsCsv()` и разделение ORM-aware `serializePostValue()`.
- Режимы рендера формы `BelongsTo`: `asSelect()` (по умолчанию), `asRadio()`, `asLink()` (preview).
- Тесты relation-слоя: namespace, runtime builder/registrar, метаданные resolver, value loader, object mutator, manual pivot sync, маршрутизация веток FormPage.

### Performance
- `BelongsTo` / `BelongsToMany` / `EntitySelect` (и наследник `UserSelect`): `previewValue()` мемоизирует подписи по значению/ID на уровне поля — повторяющиеся FK в гриде больше не дают запрос-на-строку (N+1), а резолвятся одним батчем закэшированных значений.
- `Grid\Row\RowAssembler::prepareRow()` / `buildRows()`: прямой обход массивов полей и действий вместо пересоздания `AdminCollection` на каждую строку грида.

### Fixed
- `Preview` (`badge()` / `link()` / `format()`), `Color`, `Image`: HTML из `previewValue()` больше не экранируется на index/detail — введён флаг `Field::previewReturnsHtml()`, базовые `renderIndex()` / `renderDetail()` отдают разметку как есть (динамические значения по-прежнему экранируются внутри полей).
- Поле `File`: подписи кнопок «удалить» / «выбрать файл» больше не выводятся как литеральный код `{LocalizedMessage::get(...)}` (heredoc не интерполирует статические вызовы) — строки вычисляются заранее и берутся из `lang/*/src/Field/File.php`.
- Поддержка `readonly()` / `readonlyOnUpdate()` для редактируемых полей `Checkbox`, `Color`, `Html`, `Password`, `File`, `Switcher` (раньше readonly на них молча игнорировался); `Switcher` в readonly рендерит неактивное состояние + hidden input с текущим значением, `File` скрывает загрузку и удаление.
- `Checkbox`: добавлены устойчивый `normalize()` / `serializePostValue()` (как у `Switcher`) и экранирование подписи; `Checkbox` и `Switcher` используют общий трейт `Concerns\HasCheckedValues` вместо дублированной checked-логики.
- `Password`: строки UI (`Show password`, подсказки) вынесены в `lang/*/src/Field/Password.php` через `LocalizedMessage`.
- Поля `Email`, `Date`, `DateTime`: `renderFormField()` снова получает контекст формы (`$formData`) — `readonly` / `disabled` (и для `Email` `placeholder` / реактивные атрибуты) больше не теряются; базовый `Field::renderForm()` сохраняет `formData` для полей с одноаргументной сигнатурой.
- `AdminKitManager`: кэш fingerprint для `discover()` — повторные вызовы `registry()` / `router()` / `menuBuilder()` не пересканируют пути.
- `FormPage::formTabs()`: вкладки через `Tabs` / `TabsRenderer` (`MB.AdminKit`), убран legacy `MB.UI.Tabs`.
- `DataPipeline`: `readonlyOnUpdate()` / `readonlyWhen()` учитывают `_mode`, `_id`, `ID` из raw POST при пропуске валидации.
- Scalar fields (`Text`, `Textarea`, `Number`, `Select`, `EntitySelect`): `isReadOnlyFor()` в HTML формы.
- `EntityObjectFormSaver`: сохранение в `TransactionManager` при `useTransactions()`.
- `MassDeleteAction`: атомарное удаление через `massDelete()` когда ресурс поддерживает транзакции.
- `ToolbarRenderer`: открытие create в SidePanel через `SidePanelAdapter`.
- Восстановлены BC-алиасы `Page\IndexPage`, `Page\FormPage`, `Page\DetailPage` → `Page\Crud\*`.
- `OptionsPage`: убран дублирующий inline CSS (стили в bundle `admin-common.css`).
- `OptionsPage`: вкладки `Tabs` снова отдают поля в HTML без JS (`TabsRenderer` server-prerendered fallback); обычный POST сохраняет и редиректит (AJAX только по `X-Requested-With`, убран всегда включённый hidden `adminkit_ajax`).
- `GridDataLoader`: подсчёт строк (`useTotalCount`) регистрирует `runtime` через ORM `query()`, как `getList()` — фильтры вроде `PROPERTY.IBLOCK_ID` больше не падают в `getCount()`.
- `BelongsTo` по умолчанию writable (`readonly = false`): FK-поля снова попадают в `collectAllFields()` и сохраняются через EntityObject.
- `Switcher::normalize()` / `serializePostValue()` трактуют отсутствие POST-значения как unchecked; `EntityObjectFormSaver::extractRaw()` сериализует все поля, даже если ключ не пришёл в POST.
- `BelongsToMany` без `mediatorReferences()` (pivot только с колонками, как `EventMessageSiteTable`) не регистрирует runtime ManyToMany; загрузка/сохранение через manual pivot sync.
- `readonlyOnUpdate()` / `readonlyOnCreate()` и контекст формы (`_mode`, `_id`) для `readonlyWhen()` / `isReadOnlyFor()`; `collectAllFields()` и `EntityObjectFormSaver` учитывают условный readonly.
- `BelongsTo::asLink()` на create с `default()` рендерит preview + hidden input; на edit с `readonlyOnUpdate()` — только preview.
- `FormPage::resolveFieldValueForField()` применяет `default()` при открытии формы создания.
- `Switcher::isCheckedValue()` понимает boolean/`1` из Bitrix ORM (`ACTIVE` в SectionTable) — форма и грид больше не показывают «Активен» всегда выключенным.
- SidePanel async save: перезагрузка грида через `window.parent`, `reloadTable()` и `gridId` в JSON-ответе (раньше искали только `window.top` и `reload()`).
- `FormPage`: при `adminkit_async_save=Y` успешное сохранение больше не вызывает `LocalRedirect` — ответ отдаётся JSON (`sendAsyncSaveResponse`), исправлена ошибка `Unexpected token '<'` в async-форме.
- `EntityObjectFormSaver`: после update не вызывается `UpdateResult::getId()` с пустым `primary` — ID берётся из EntityObject, `itemId` или `getPrimary()` на Result.
- `FormPage` / `EntityObjectFormSaver`: при исключениях сохранения в глобальные ошибки выводятся сообщение, файл:строка и stack trace (`ExceptionDiagnostics`).
- SidePanel async save: перед закрытием слайдера выполняется `reloadItemAfterSave()` — ошибки пост-загрузки (в т.ч. relation) попадают в JSON `globalErrors`, панель не закрывается; `form-save.js` показывает алерты, уведомление и не считает ответ успешным при `globalErrors` / `fieldErrors`.
- `BelongsToMany::normalize()` в ORM-режиме сохраняет список ID (раньше наследовался `BelongsTo::normalize()` и оставлял только первый элемент).
- `MediatorPivotKeyResolver`: pivot-колонки (`USER_ID`, `GROUP_ID`) выводятся из `mediatorReferences()` на pivot-сущности; `RelationMetadataResolver` и `persistsViaPivotTable()` используют manual pivot sync без обязательного `foreignPivotKey()` / `relatedPivotKey()` в DSL.
- `MediatorReferenceOrientation`: `mediatorReferences()` на `GroupTable` с `('USER', 'GROUP')` автоматически приводится к `('GROUP', 'USER')` — исправлена ошибка `Unknown field definition UserGroup:USER for Group Entity` при `findObject()`.
- `HasMany` preview: `RelationValueLoader` разворачивает `EntityObject` / коллекции в скалярные массивы; при явном `relatedTable()` + `foreignKey()` подгружает все pivot-строки через `getList`, если ORM отдал меньше записей; `renderTablePreview()` больше не приводит объекты к string.
- `RelationTileGrid` preview: уникальные id строк (`row_0`, `row_1`, …), чтобы tilegrid не схлопывал строки с одинаковым `ID`; подписи колонок всегда приводятся к строке (ORM title / JSON), в заголовке больше не отображается `[object Object]`.
- `HasMany::asTable()`: превью связей через Bitrix `ui.tilegrid` (`RelationTileGridPreviewRenderer`, `BX.TileGrid.Grid`, кастомные `BX.AdminKit.RelationTileGrid.*Item`); в `asTable()` можно задать колонки и подписи (без подписи — `getTitle()` из ORM related-таблицы).
- `BelongsTo::normalize()` приводит пустой FK к `null` и числовые ID к `int`, чтобы `EntityObject::save()` не получал `''` для integer-полей.
- `FormPage` передаёт validated-значения из `beforeValidate()` (например `IBLOCK_ID` на create) в `EntityObjectFormSaver`, в том числе для readonly-полей.
- `DataManagerResource::assertSinglePrimaryKey()` не вызывает `count()` при `getPrimaryArray() === null`.
- `RuntimeRelationBuilder` для ManyToMany: `configureLocalReference` / `configureRemoteReference` принимают имена Reference на mediator-сущности, а не имена pivot-колонок; обязателен DSL `mediatorReferences()` (без привязки к конкретным Bitrix-модулям).
- `BelongsTo::relatedTable()` синхронизирует `dataManagerClass` для загрузки опций в `BelongsToMany`; `BelongsToMany::renderFormField()` принимает список ID из `resolveRelationValue()`.
- `EntityObjectFormSaver` откладывает sync связей до первого `save()` при создании записи; `RelationObjectMutator` создаёт пустую коллекцию через `DataManager::getEntity()->createCollection()`.
- `BelongsToMany` с явным `pivotTable()` / pivot keys сохраняется через pivot sync (`persistsViaPivotTable()`): runtime ManyToMany в Bitrix доступен для чтения, но `EntityObject::set()` для него не поддерживается.
- `RelationMetadataResolver` отдаёт приоритет явным `foreignPivotKey()` / `relatedPivotKey()` над ORM metadata; `OrmRelationResolver` больше не пишет имена Reference (`IBLOCK_ELEMENT`) в pivot-колонки.
- `RelationValueLoader` корректно разворачивает строки-массивы, duck-typed entity objects и Bitrix `Collection` для BelongsToMany в ORM-режиме.
- `RelationObjectMutator` применяет BelongsTo FK, BelongsToMany collection/manual sync и защищённые обновления HasOne/HasMany (без тихого удаления без явных флагов).
- `ManualPivotSynchronizer` реализует `RelationSynchronizerInterface` с diff pivot (insert/delete/keep).
- `OrmRelationResolver` извлекает ключи метаданных связей из API полей Bitrix ORM, где это доступно.
- `RuntimeRelationBuilder`: корректный обратный Reference для BelongsTo/HasOne, OneToMany, ManyToMany с явными pivot keys.
- Метаданные inline-редактирования грида учитывают readonly полей: поля с `readonly()` больше не публикуют editable-конфиг в колонках `main.ui.grid`.
- Relation и entity selector поля явно помечены как non-inline-editable в метаданных грида, чтобы избежать нестабильных runtime-редакторов и направить редактирование в form/sidepanel.
- Метаданные колонки `Select` экспортируют editable `items` только когда inline-редактирование для колонки реально включено.
- Метаданные грида `BelongsTo` (и `BelongsToMany`) явно отключают inline-редактирование, согласуя relation-подобные select со стабильными flow sidepanel/form и исправляя падающие тесты/CI.
- Исправлены локальные сбои CI: удалён отладочный вызов Bitrix `Debug::writeToFile()` из mass delete, восстановлен алиас колбэка `BulkAction::executeUsing()`, добавлены недостающие PHPStan-стабы Bitrix.
- AJAX-ответы bulk actions теперь содержат структурированные `status`, `errors`, `warnings`, `affected` и сводку; non-AJAX flash включает ошибки/предупреждения по строкам, чтобы сообщения сохранялись после перезагрузки.
- `BitrixGridActionPanelAdapter` больше не выбирает JavaScript-обработчик export по магическому id `export_selected`; bulk actions могут объявлять `clientHandler()`.
- Стабильность Composer-пакета по умолчанию `stable`; пакет больше не подталкивает потребителей к разрешению dev-зависимостей.

### Documentation
- Этап 7 документации: выполнен финальный audit навигации и cookbook, добавлен `docs/quality-checklist.md`, обновлены карта документации (`docs/README.md`) и cookbook-рецепты (`docs/user/cookbook/README.md`, `filter.md`, `row-action.md`, `bulk-action.md`) с единым шаблоном «Задача/Решение/Полный пример/Что важно учесть/Связанные разделы», уточнены Bitrix-native и bulk-security акценты без изменений runtime-кода.
- Восстановлен обзорный раздел `docs/fields.md`: возвращено практическое описание fluent API `Field` (visibility, validation, readonly, formatting, grid, computed, import/export, multiple, dependsOn, when) с примерами и областями применения, без превращения страницы во внутренний API reference; сохранены каталоги стандартных и relation-полей, а также пояснение по runtime-методам `displayValue()`/`previewValue()`.
- Упрощён `docs/fields.md`: документ сфокусирован на пользовательском Fluent API и практических сценариях (`Resource`/`OptionsPage`), без детального internal API-reference; отдельно подчеркнуто, что `displayValue()`/`previewValue()` — runtime-методы, а для настройки отображения используются `displayUsing()`/`format()`/`preview()`, и добавлена ссылка на relation guide `docs/user/guides/relations.md`.
- Синхронизирован `docs/fields.md` с фактическими concrete-полями из `src/Field/*` и `src/Field/Relation/*`: раздел превращён в обзорный каталог со ссылками на отдельные страницы `docs/user/reference/fields/*`, добавлены явные ссылки на relation guide и уточнено текущее поведение `Slug` (без `from()` как `Text`, с `from()` реактивная генерация).
- Полностью переписан `docs/fields.md`: добавлено полноценное описание базового Field API (identity/value/default/visibility/conditions/reactivity/validation/readonly/grid/computed/export-import/render lifecycle/custom field) и актуальный обзор стандартных полей с ссылкой на relation-гайд.
- Added `docs/user/reference/fields/slug.md` and updated fields reference index with Slug field usage, defaults, and `dependsOn()` behavior.
- В `docs/user/reference/fields/select.md` возвращён базовый пример `options([...])` через массив, перед примерами `Closure` и `OptionsResolverContract`.
- В docs/user/reference/fields/select.md добавлены отдельные примеры использования options() через Closure и через OptionsResolverContract (включая кастомный resolver).
- Уточнён `docs/user/reference/fields/field.md`: в разделе ключевых методов оставлены только методы, влияющие на поведение/рендер/логику поля; методы-чтения вынесены из основного списка.
- Добавлены разделы `Значения по умолчанию` в `docs/user/reference/fields/*.md` с дефолтами конкретных классов и переопределяемыми дефолтами базового `Field`.
- Для каждого файла в `docs/user/reference/fields/*.md` добавлены разделы `Методы и что делают` с расшифровкой назначения методов (включая inherited API там, где у класса нет собственных fluent-методов).
- Раздел `Reference: Components` декомпозирован в каталог `docs/user/reference/components/`: добавлен общий `component.md` и отдельные `.md` по каждому компоненту и layout-классу (`Alert`, `Badge`, `SidePanel`, `Tabs`, `Grid` и др.).
- Раздел `Reference: Fields` декомпозирован в отдельный каталог `docs/user/reference/fields/`: добавлен общий `field.md` и отдельные `.md` по каждому полю/семейству (`Text`, `Select`, `File`, `EntitySelect`, relation fields и т.д.).
- Добавлены два отдельные end-to-end гайда подключения от установки пакета: `getting-started/module-full-guide.md` (внутри модуля) и `getting-started/standalone-full-guide.md` (вне модуля через `local/php_interface/init.php`).
- Расширены user-гайды `getting-started/installation` и `getting-started/bootstrap`: добавлены пошаговые сценарии для module и standalone, варианты подключения `vendor/autoload.php` (module/local/project vendor) и отдельный standalone-пример через `local/php_interface/init.php`.
- Полностью переработана пользовательская документация под структуру `README -> docs/user/getting-started -> docs/user/guides -> docs/user/reference`, cookbook вынесен в `docs/user/cookbook`.
- Объединены дубли по import/export и lifecycle, добавлены канонические правила export (`allowExportByFilter`, `allowExportAll`, `maxExportRows`) и единый раздел ограничений текущей версии.
- Добавлены новые пользовательские разделы по `AsyncAction`, `PermissionContext` (матрица проверок), `Resource` выбору (`Resource`/`CrudResource`/`DataManagerResource`) и UI-компонентам (`Badge`, `Button`, `Heading`, `Notification`, `SidePanel`, layout).
- Внутренние материалы для контрибьюторов вынесены в `docs/dev/*`; исправлены устаревшие namespace/классы в примерах и ссылках.
- Задокументирован lifecycle рендера полей и совместимость/ограничения inline-редактирования для базовых, select, relation и entity selector полей.
- Уточнено, что `CrudResource` — DSL/база страниц без persistence, а ORM CRUD-ресурсы должны расширять `DataManagerResource`.
- Синхронизирована документация import/export, grid, quick-start и bulk-action с текущим состоянием: export включён, import UI отключён.

### Documentation
- Этап 7 документации: выполнен финальный audit навигации и cookbook, добавлен `docs/quality-checklist.md`, обновлены карта документации (`docs/README.md`) и cookbook-рецепты (`docs/user/cookbook/README.md`, `filter.md`, `row-action.md`, `bulk-action.md`) с единым шаблоном «Задача/Решение/Полный пример/Что важно учесть/Связанные разделы», уточнены Bitrix-native и bulk-security акценты без изменений runtime-кода.
- Добавлены и переработаны пользовательские входные документы верхнего уровня: `docs/README.md`, `docs/installation.md`, `docs/quick-start.md`.
- Упрощён корневой `README.md`: оставлены позиционирование, требования, установка, минимальный валидный ORM Resource-пример и ссылки на ключевые разделы.
- Добавлены верхнеуровневые навигационные страницы `docs/resources.md`, `docs/pages.md`, `docs/grid.md`, `docs/actions.md`, `docs/bulk-actions.md`, `docs/options-page.md`, `docs/dashboard-page.md` для единых коротких ссылок из README/карты документации.
- Этап 2 оптимизации документации: исправлены относительные ссылки в `docs/README.md`; переработаны `docs/resources.md` и `docs/pages.md` как полноценные пользовательские разделы; расширены API-справочники `docs/user/reference/resources.md` и `docs/user/reference/pages.md`; обновлён гайд выбора базового класса `docs/user/guides/resource-selection.md` в соответствии с текущим публичным API (`Resource` / `CrudResource` / `DataManagerResource`, CRUD/standalone Pages).

### Stabilization
- Test runner no longer aborts silently on JSON/early-response branches: response termination is centralized and test-aware (`Support\ResponseTerminator`), so `composer test` always returns a full summary.
- Restored legacy `Resource` behavior expected by v1 modules: default CRUD page helpers (`indexPage/formPage/detailPage`), menu/permission/grid defaults, and DataManager fallback compatibility for direct `Resource` + persistence-trait usage.
- Restored legacy page aliases `Page\IndexPage`, `Page\FormPage`, `Page\DetailPage` as compatibility wrappers over `Page\Crud\*` (deprecated wrappers retained).
- Fixed standalone-page registration safety (`AdminKitRegistry` now validates type before calling standalone methods).
- Fixed menu builder safety for non-CRUD resources (guarded calls to optional `canView`/`hasCrud`/`group`).
- Fixed field display pipeline double-formatting (`displayUsing`/preview no longer double-applies formatting).
- Fixed `UserListProvider` boolean filter conditions (`onlyWithEmail`, `invitedUsers`).
- Fixed EntitySelector providers stability in minimal/runtime test environments: safe defaults for missing `selected`/name-template context and guarded user-availability checks (`UserListProvider`, `UserGroupListProvider`).
- Fixed selected export filtering to use primary-key filtering compatible with existing resource expectations.
- Added inline-edit BC bridge on `IndexPage::saveInlineRow()` and visibility-rule BC helper on `Pages\OptionsPage::checkVisibilityRule()`.
- Updated JS extension tests/fixtures to current `mb.admin.kit` bundle layout and stabilized isolated test fixtures.
- Refreshed PHPStan baseline (`phpstan-baseline.neon`) to separate current known issues from new regressions.

### Removed
- Unused legacy field traits in `Field\Traits\*` (`HasValidation`, `HasFormat`, `HasVisibility`, `HasReactivity`, `Makeable`); use `Field\Concerns\*` instead.
- Unused `Resource\Concerns\HasResourcePages` trait (superseded by `HasCrudResourcePages`).
- Standalone Bitrix JS extensions `mb.ui.tabs` and `mb.ui.dialog-selector`; Tabs and DialogSelector runtime now ship inside `mb.admin.kit`.
- Legacy contract aliases in `MB\Bitrix\AdminKit\Contracts\` (`IndexResourceContract`, `FormResourceContract`, `OrmResourceContract`, `ExportResourceContract`, and others) — use `Contracts\Resource\*` instead.
- Legacy resource traits `HasCrud`, `HasPermissions`, `HasLifecycleEvents` — use `Resource\Concerns\*` instead.
- Deprecated `Page\OptionsPage` wrapper — use `Pages\OptionsPage`.
- Deprecated page aliases: `Pages\AbstractPage`, `Pages\CustomPage`, `Pages\DashboardPage`, `Page\IndexPage`, `Page\FormPage`, `Page\DetailPage`, `Contracts\PageContract` — use `Page\StandalonePage`, `Page\Standalone\*`, `Page\Crud\*`, and `Contracts\Page\*` instead.
- Unused grid panel classes `Grid\Panel\PanelDataProvider`, `Grid\Panel\BulkDeletePanelAction`.
- `AdminKitJs::renderGridCollapsibleInitialState()`, `Tab::getFields()`, `HasResourceExport::maxImportRows()`.
- Wide contracts `Contracts\FieldContract` and `Contracts\ComponentContract`; use `Contracts\Field\*`, `Contracts\UI\*`, and `Contracts\Widget\*`.

### Added
- Added `AdminKitScope::fromModuleId()` and `resolveModulePath()` for module-relative resource/page discovery without replacing explicit `Loader::includeModule()` bootstrap.
- `Support\LocalizedMessage` — shared `Loc::getMessage` helper for user-facing strings (replaces duplicated private `message()` methods).
- `Pages\Handlers\OptionsPagePostHandler` and `Pages\Handlers\OptionsPageFormRenderer` — extracted POST and form rendering from `Pages\OptionsPage`.
- `Tabs::remember()` — stores the last active tab in session (per page id) and restores it on the next visit; hidden `adminkit_active_tab` field syncs tab clicks when remember is enabled.
- `Field::preserveStoredValueWhenEmpty()` — used by `Password` so an empty submit keeps the stored option value instead of deleting it.
- `Password::oldValue()` (default `true`) — shows the stored value with a show/hide toggle; `oldValue(false)` keeps the previous empty-field edit behavior.
- New Resource architecture: `Resource` (core) -> `CrudResource` (DSL) -> `DataManagerResource` (ORM).
- Logic extracted into reusable concerns: `HasResourceIdentity`, `HasResourceMenu`, `HasResourcePages`, `HasResourceFields`, `HasResourceFilters`, `HasResourceActions`, `HasResourceAuthorization`, `HasResourceSidePanel`, `HasResourceGrid`, `HasResourceQuery`, `HasResourceGrouping`, `HasResourceExport`, `HasResourceLifecycle`, `HasDataManager`, `HasDataManagerPersistence`.
- Narrow resource contracts in `Contracts\Resource\*` for better dependency management.
- `DataManagerResource` as the preferred base class for ORM-backed resources.
- `ResourceActionsContract` with `hasAction()` and `activeActions()` for granular action control.
- `RelationField` is now `readonly(true)` by default to prevent accidental saving of relation data.
- CSRF protection for all bulk actions (added `sessid` to action panel JS).
- New UI contracts: `Contracts\UI\RenderableContract`, `ComponentContract`, `LayoutComponentContract`, `FieldContainerContract`, `ItemAwareContract`, `PageTypeAwareContract`, `HtmlAttributesContract`, `ConditionalVisibilityContract`, `AssetAwareContract`.
- New field contracts in `Contracts\Field\*` with aggregate `Contracts\Field\FieldContract`.
- New renderers: `Field\Renderers\FieldRowRenderer`, `Component\Renderers\ChildrenRenderer`, `Component\Renderers\VisibilityWrapper`, `Widget\Dashboard\DashboardRenderer`.
- New options resolver stack for `Select`: `Field\Options\*Resolver`.
- New chart architecture: `Widget\ChartWidget` + `Widget\Renderers\ChartWidgetRenderer` (`GraphWidget` now compatibility alias).
- `BulkActionDropdown::placeholder()`, `withoutPlaceholder()`, and placeholder accessors for controlling the first Bitrix dropdown item.
- `BulkAction::allowRunWithoutFilter()` for explicitly allowing guarded full-table bulk actions, plus `BulkOperationContext::$forAll`.
- Bulk action `groupSort()` support for deterministic action-panel group ordering.

### Changed
- Updated bootstrap/discovery documentation and demo module examples to separate Bitrix-module and local-admin usage scenarios.
- `AdminKitScope::fromModule(string)` now treats string values as Bitrix module IDs and delegates to `fromModuleId()`.
- Tabs and DialogSelector ship as separate bundles in `mb.admin.kit` (`src/tabs`, `src/dialog-selector` → `dist/*.bundle.js`); no shared `initAll` — each `TabsRenderer` / `DialogSelectorRenderer` initializes its own instance.
- `Tabs::extension()` removed; `AssetManager::forForm()` no longer loads `mb.ui.tabs` separately.
- `Resource` is now a minimal core class for identity, menu, and pages.
- `CrudResource` is now a DSL-only class without persistence logic.
- All ORM-backed examples and fixtures migrated to `DataManagerResource`.
- Internal services (`GridDataLoader`, `IndexPage`, `FormPage`, `DetailPage`, `ExportAction`) updated to use narrow contracts.
- `HasDataManagerPersistence::findItem` now uses explicit `=` operator for primary key filter.
- `ExportAction` now uses explicit `@` operator for selected IDs filter.
- `OptionsPage::fields()` is now `public` in documentation and examples.
- `Field` base class moved to concern-based composition (`Field\Concerns\*`) and no longer acts as a monolith.
- `AbstractLayoutComponent` delegates child rendering to `ChildrenRenderer` and no longer renders field rows directly.
- `FormPage` and `OptionsPage` now render form rows via `FieldRowRenderer`.
- `Tabs` rendering is delegated to `TabsRenderer`/`TabBodyRenderer`; duplicate field-row rendering and debug `console.log` removed.
- `AbstractWidget` is now a leaf dashboard component (no inheritance from layout containers).
- Dashboard rendering and extension collection moved from `DashboardPage` into `Widget\Dashboard\DashboardRenderer`.
- Chart widgets no longer load external Chart.js CDN from PHP; chart init is local-extension driven.
- `BulkAction::delete()` now returns `MassDeleteAction` directly; the index bulk handler no longer substitutes delete actions at runtime.
- `MassDeleteAction` now shares the delete factory UI defaults (`danger`, remove icon, danger group, confirmation, sort order).

### Fixed
- Grid inline-edit metadata now respects field readonly state: `readonly()` fields no longer publish editable config into `main.ui.grid` columns.
- Relation and entity selector fields are now explicitly non-inline-editable in grid metadata to avoid unstable runtime editors and enforce form/sidepanel editing flows.
- `Select` grid column metadata now exports editable `items` only when inline editing is actually enabled for the column.
- `BelongsTo` (and `BelongsToMany`) grid metadata now explicitly disables inline editing, aligning relation-like selects with stable sidepanel/form editing flows and fixing failing tests/CI checks.
- Localized hardcoded user-facing messages in actions, bulk results, relation components, selectors, validation rules, import/export handlers, and CRUD page notifications by moving them to `Loc` message files (`lang/ru` and `lang/en` for the affected classes).
- Updated key documentation files to Russian and synchronized quick-start/install/architecture/grid/import-export/backward-compatibility guides with the current package behavior.
- Options page: fields on inactive Bitrix tabs (e.g. `BelongsTo`) are included in AJAX save by temporarily enabling disabled inputs before `FormData` is built.
- Options page: `Password` and other fields with `preserveStoredValueWhenEmpty()` no longer clear stored values when the posted value is empty.
- Options page: remembered tab id is applied before render so the correct tab is active after reload.
- CSRF: added missing `sessid` to native Bitrix grid bulk actions.
- ORM: fixed potential issues with ambiguous primary key filters by using explicit Bitrix ORM operators.
- `IndexPage`: restored base `grouping()` hook so `IndexPageDefinition` and `RowAssembler` receive resource `indexGrouping()` (group rows were missing when only collapsible UI was enabled).
- Group labels: `GroupLabelRenderer` now resolves section titles from `__GROUP_DATA` (`NAME`/`TITLE` fallback) and renders `ungroupedLabel()` for the ungrouped bucket; collapsible shift column prefers `NAME` when grouping has no explicit `labelColumn`.
- Bulk action dropdowns now render their label as a non-executable placeholder item, so Bitrix `Types::DROPDOWN` shows the dropdown label/placeholder instead of the first child action.
- Invisible direct bulk actions and invisible dropdown child actions are filtered out of `ACTION_PANEL`; dropdowns with no visible executable children are skipped.
- For-all bulk mode now uses the explicit `action_all_rows_<GRID_ID>` checkbox flag and ignores selected IDs in that mode.
- `QueryGuard` now blocks unsafe for-all operations without `allowRunByFilter()`, empty-filter/full-table operations without `allowRunWithoutFilter()`, and operations above `maxBulkRows()`.
- Custom `BulkAction` handlers now honor action-level `canRun()` before invoking the handler.

### Changed
- Grouped index grids initialize collapsible rows via `AdminKitJs::renderInit('GridCollapsible')` on `IndexPage` instead of auto-starting from `mb.admin.kit` bundle entry.
- `Resource` — BC base (identity, menu, permissions, pages, export defaults); `CrudResource` — thin ORM layer (`dataManagerClass()`, `hasCrud(): true`) without duplicated defaults.
- Grid split: `GridQueryBuilder` (ORM params only), `GridDataLoader` (load/count/cache), `Grid` + Bitrix adapters (UI); `IndexPage` delegates to these services.
- `FormPage` / `DetailPage` — page-level `fields()` / `tabs()` are primary; resource shortcuts are fallbacks.
- `Pages\OptionsPage` stabilized (sessid, JSON array options, `visibleWhen` aligned with `FormPage`); `Page\OptionsPage` deprecated wrapper retained.
- Routing: `admin_resource` + `admin_page` alongside legacy `page` / `action`.
- Export: removed legacy `Support\Export\CsvExporter`; use `Export\CsvExporter` + `ExportAction`.
- Discovery: multi-path, safe missing directories, duplicate-id preservation; standalone pages via `AbstractPage::isStandalone()`.
- Documentation: README, `docs/pages.md`, `docs/grid.md`, `docs/discovery.md`, `docs/architecture.md`, `docs/upgrade.md`, export-only `docs/import-export.md`, import-disabled `docs/import.md`.

### Removed / disabled
- Import UI and toolbar entrypoints removed from `IndexPage` (no `action=import`, no import SidePanel flow on index). Library `Import\*` classes remain for future re-enable.

### Fixed
- Grid inline-edit metadata now respects field readonly state: `readonly()` fields no longer publish editable config into `main.ui.grid` columns.
- Relation and entity selector fields are now explicitly non-inline-editable in grid metadata to avoid unstable runtime editors and enforce form/sidepanel editing flows.
- `Select` grid column metadata now exports editable `items` only when inline editing is actually enabled for the column.
- `BelongsTo` (and `BelongsToMany`) grid metadata now explicitly disables inline editing, aligning relation-like selects with stable sidepanel/form editing flows and fixing failing tests/CI checks.
- CSRF: POST saves and options updates require valid sessid; AJAX returns JSON errors, normal POST shows alert.
- `DetailPage` enforces `canView` before rendering a record.
- `FormPage` validation/save lifecycle and permission checks (`canCreate` / `canUpdate`) on render and save.
- `FormPage` async SidePanel save returns JSON via `sendAsyncSaveResponse()` instead of full-page redirect.
- String primary-key delete on index grid.
- `GridQueryBuilder::buildOrder()` three-layer sort merge.
- Export guard messages localized; export failures use `ui.alerts` on index.
- Form/toolbar RU label mojibake (UTF-8 `Loc` files).

### Documentation
- Этап 7 документации: выполнен финальный audit навигации и cookbook, добавлен `docs/quality-checklist.md`, обновлены карта документации (`docs/README.md`) и cookbook-рецепты (`docs/user/cookbook/README.md`, `filter.md`, `row-action.md`, `bulk-action.md`) с единым шаблоном «Задача/Решение/Полный пример/Что важно учесть/Связанные разделы», уточнены Bitrix-native и bulk-security акценты без изменений runtime-кода.
- Полировка этапа 6: в документации выровнены markdown-таблицы и оформление примеров как fenced code blocks с языками.
- Исправлен namespace фильтра в примере `docs/grid.md` на `MB\Bitrix\AdminKit\Filter\Types\TextFilter`.
- Уточнена секция выбора режима в `docs/user/guides/bulk-actions.md` в виде markdown-таблицы.

## v1.0.0 - 2026-05-14

### Changed
- Split Bitrix grid architecture into `GridQueryBuilder` for ORM params, `GridDataLoader` for DataManager loading/count/cache, `Grid` for state, Bitrix grid/filter/action-panel adapters for component params, and `ToolbarRenderer` for toolbar/filter/create integration.
- Removed ORM query construction from `Grid` and made `IndexPage` delegate data loading and toolbar rendering to dedicated services.
- Documented the new grid layering in `docs/grid.md` and added coverage for the new query/data/UI boundaries.

### Added
- Added scoped AdminKit creation with `AdminKitScope`, `forModule()`, `forScope()`, `fromDirectory()`, and `fromDirectories()` for module, `local/php_interface`, custom-directory, and manual-registration workflows.
- Added multi-path discovery configuration and registry discovery that safely ignores missing paths and skips abstract classes.
- Documented scope-based discovery in README and `docs/discovery.md`.

### Added
- Added the `MB\Bitrix\AdminKit\AdminKit` facade for creating per-module managers.
- Documented the v1.0.0 stable public API review scope for Resources, CRUD resources, grid/form contexts, database result objects, fields, filters, actions, support adapters, URL generation, and import/export.
- Added a backward-compatibility policy for v1.x covering public/protected method signatures, class names, namespaces, CRUD behavior, `FormData`, `GridContext`, `DbResult`, `BulkResult`, and base Field/Filter/Action APIs.
- Added v1.0.0 documentation pages for installation, resources, CRUD resources, database integration, grid, filters, forms, actions, lifecycle, import/export, and backward compatibility.
- Added compatibility coverage for stable public class loading and avoiding direct global support helper declarations/calls.
- Added focused v1.0.0 examples for simple CRUD, product resources, runtime fields, computed columns, bulk actions, Bitrix field adapters, database health, and CSV import/export.
- Added a root `phpstan.neon` entrypoint and pointed the Composer analysis script at it while keeping the level 6 configuration in `phpstan.neon.dist`.
- Added v1.0.0 agent notes to preserve stable public APIs, support package adapter boundaries, and migration documentation.

### Changed
- Expanded the README with stable API, lifecycle, transaction, permission, database health, performance, Bitrix UI adapter, documentation, and examples guidance for v1.0.0.
- Confirmed Composer runtime requirements stay on PHP `^8.2` and support packages `mb4it/collections`, `mb4it/stringable`, and `mb4it/conditionable` `^1.0`.
- Restored legacy selector aliases (`UserSelect`, `EntitySelect`, `IblockElementSelect`) as first-class adapters rendered through `mb.ui.dialog-selector`/`MB.UI.DialogSelector`, while keeping `*SelectorField` classes available.
- Reworked bulk action panel execution to use native `main.ui.grid` `reloadTable('POST', ...)` flow (with `action_button_{GRID_ID}` and `action_all_rows_{GRID_ID}`), while keeping legacy `adminkit_bulk_action` JSON handling for backward compatibility.
- Hardened server-side bulk request parsing for `action_button_{GRID_ID}`, `controls[group_action]`, and multiple selected-ID keys (`id`, `ID`, `rows`, primary-key aliases).
- Added row action SidePanel close handlers that reload the bound grid after edit/view sliders close.
- Added native inline grid editing support: enabled `ALLOW_INLINE_EDIT`/`ALLOW_EDIT_SELECTION` for editable fields and handled `action_button_{GRID_ID}=edit` row saves through `DataPipeline` and CRUD persistence.
- Simplified selector field rendering by unifying `EntitySelectorField` form output through the `mb.ui.dialog-selector` path used by selector aliases, reducing dual-engine complexity.
- Replaced `*SelectorField` selector classes with `*Select` classes (`EntitySelect`, `DialogSelect`, `TagSelect`, `UserSelect`, `IblockElementSelect`, `IblockSectionSelect`) and removed old `*SelectorField` classes.
- Updated `EntitySelect` label resolution to use `src/UI/EntitySelector` providers by default; `resolveLabels()` now serves as an optional custom-label override.
- Improved `IblockElementListProvider` avatar resolution for selector items: now uses `PREVIEW_PICTURE`, then `DETAIL_PICTURE`, then `MORE_PHOTO` fallback.
- Restored legacy `IblockElementSelect` entity binding to `iblock-element-list` and legacy label resolution behavior from previous support package implementation.
- Added toolbar buttons for CSV export/import and wired `action=export`/`action=import` handling in `IndexPage` to execute `ExportAction` and `ImportAction`.
- Updated import UX: `action=import` now opens in SidePanel and uses AdminKit `ui-form`/Field-based layout instead of a raw standalone markup block.
- Reworked CSV import UI to a staged flow using Bitrix `ui.stepprocessing` (`parse` -> `validate` -> `import`) inside SidePanel and removed the `К списку` action from the import form.
- Temporarily removed import entrypoints from resource index pages and toolbar, and added configurable resource toolbar actions with built-in `export` action support.
- Localized export guard/error messages via Bitrix `Loc` and ensured export failures render with styled `ui.alerts` notifications on index pages.
- Localized index and toolbar user-facing labels/messages (create/export button labels, inline row error template, and export fallback message) via Bitrix `Loc` keys.
- Unified toolbar management across `IndexPage`, `FormPage`, and `DetailPage` using Bitrix `Toolbar` facade patterns (top save/cancel on form, back/edit on detail, and localized toolbar labels).

### Migration notes
- v1.0.0 is a stabilization release, not a feature expansion release. Existing v0.1.0-v0.9.0 Resource, Field, Filter, Action, persistence, bulk action, import/export, and page-layer extension points remain the migration path.
- Userland code should depend on `MB\Bitrix\AdminKit\Support\AdminCollection`, `AdminString`, and `AdminCondition` only when an adapter is actually needed; public Resource APIs should continue to expose plain PHP values.

### Known limitations
- Import/export remains CSV-first.
- Database health pages are diagnostic/read-only by default and do not replace migrations.
- Bitrix UI selector fields wrap Bitrix selector assets and providers; they do not provide a custom selector engine.

## v0.9.0 - 2026-05-14

### Added
- Rewrote README as a complete DX guide covering installation, Bitrix module wiring, Resources, CRUD pages, fields, filters, actions, OptionsPage, CustomPage, SidePanel, permissions, ORM query customization, runtime fields, computed columns, import/export, and support adapters.
- Added `docs/quick-start.md` with a minimal end-to-end Bitrix module flow: ORM table, ProductResource, admin file, menu, create, edit, and delete.
- Added `examples/demo-module` with a realistic Bitrix module skeleton demonstrating Product CRUD, Text/Select/Switcher fields, TextFilter/SelectFilter, computed columns, runtime fields, row actions, bulk actions, OptionsPage, DashboardPage, and SidePanel create/edit.
- Added cookbook recipes for fields, filters, row actions, bulk actions, SidePanel, runtime ReferenceField, computed columns, custom save, lifecycle hooks, permissions, settings pages, dashboards, EntitySelectorField, and import/export.
- Added architecture and support-package documentation plus upgrade/deprecation notes.
- Added PHPStan level 6 configuration, php-cs-fixer configuration, `composer cs-check`, and GitHub Actions CI for composer validate/install/test/analyse/code-style checks.
- Added v0.9.0 agent notes to keep future DX work copy-paste friendly and backward compatible.

### Changed
- Stabilized Composer QA scripts around `composer test`, `composer analyse`, `composer cs-fix`, and dry-run `composer cs-check`.
- Documented the deprecation policy: no public API removals without `@deprecated` phpdoc and migration notes.

## v0.8.0 - 2026-05-14

### Added
- Added a backward-compatible manager split: `AdminKitRegistry`, `AdminKitRouter`, `AdminKitMenuBuilder`, and `AdminKitRenderer`.
- Stabilized standalone page API with instance `id`, `title`, `sort`, `icon`, `group`, `canView`, `render`, and `url` methods while keeping legacy static methods.
- Added centralized URL routing helpers for CRUD pages, standalone pages, action endpoints, bulk actions, and import/export endpoints.
- Added `AssetManager`, `ToolbarAction`, `SidePanelAdapter`, and `DashboardPage`.
- Extended resources with SidePanel settings and menu grouping helpers.
- Added the simple `OptionsPage::fields()` API while preserving component-based options pages.
- Documented pages, routing, menus, options pages, custom pages, dashboards, SidePanel, toolbars, and assets.
- Added v0.8.0 tests for registry, routing, menus, page API, options/custom/dashboard pages, SidePanel, toolbar, assets, permissions, and URL routing.


## v0.7.0 - 2026-05-14

### Added
- Added CSV-first resource export with `ExportAction`, `ExportContext`, `ExportResult`, `ExporterInterface`, and `CsvExporter`.
- Added selected-record export and filter-based export while keeping full export disabled by default.
- Added export permission checks and field visibility/private/system guards so hidden or private fields are not exported.
- Added CSV import with `ImportAction`, `ImportContext`, `ImportResult`, `ImporterInterface`, and `CsvImporter`.
- Added import preview/validate-only support, column mapping, create/update/upsert modes, configurable key field, and row limits.
- Added shared `Form\DataPipeline` so form saves and imports use the same Field normalization and validation pipeline.
- Added documentation for CSV import/export formats, permissions, limits, selected exports, filter exports, preview, validate-only, and import modes.

### Changed
- Form saves now route Field normalization and validation through the shared data pipeline used by CSV imports.

## v0.6.0 - 2026-05-14

### Added
- Added read-only database schema diagnostics with `DatabaseSchemaInspector`, `TableSchema`, `TableHealthCheck`, and optional `DatabaseHealthPage`.
- Added `SchemaAwareResource` and `CrudResource::databaseTableName()` for declaring and discovering expected resource tables.
- Added query performance controls with `QueryPerformanceContext`, `QueryGuard`, `useTotalCount()`, `countCacheTtl()`, and `maxPageSize()`.
- Added TTL caching for grid counts, `Select` options, and relation lookups through request-level and optional persistent caches.
- Added documentation for database health, schema diagnostics, disabling count, count/options/lookup cache, query guard, and max page size.
- Added PHPUnit coverage for v0.6.0 schema diagnostics, health page diagnostics, count disabling/cache, options cache, lookup cache, query guard, max page size, and cache key generation.

### Changed
- Grid loading now caps requested page size using the resource `maxPageSize()` before ORM parameters are executed.
- Bulk action execution now validates selected IDs and run-by-filter safety through `QueryGuard`.

## v0.5.0 - 2026-05-14

### Added
- Added a unified Field API for index/form/detail rendering, normalization, conditional required/readonly/visible behavior, dependencies, placeholders, help text, defaults, and `displayUsing()` presentation callbacks.
- Added Bitrix UI selector adapters: `EntitySelectorField`, `UserSelectorField`, `IblockElementSelectorField`, and `IblockSectionSelectorField`, while keeping legacy selector class names available.
- Added `UfField` as an adapter over Bitrix user-field metadata and rendering.
- Added `RelationResolver` for batched request-level relation lookup caching to avoid N+1 display queries.
- Added field compatibility tests for callable options, multiple normalization, conditional behavior, selector normalization, display callbacks, and relation preloading.
- Added field documentation for the common Field API, concrete fields, Bitrix UI selector adapters, UF fields, normalization, validation, conditions, dependencies, and lookup preloading.

### Changed
- `Select` now supports callable options, correct single/multiple rendering, label rendering in index/detail views, required validation, and array-safe multiple normalization without comma implosion.
- Required validation now treats empty arrays as empty values for multiple fields.
- `Number` now normalizes empty input to `null` and numeric input to `int` or `float`.

## v0.4.0 - 2026-05-14

### Added
- Added safe bulk operation infrastructure with `BulkOperationContext`, `BulkResult`, chunked selected-ID processing, per-row permission checks, and user-facing operation summaries.
- Added fluent bulk action APIs for labels, confirmation, danger styling, visibility/run conditions, callback handlers, simple bulk updates, and opt-in run-by-filter support.
- Added `MassDeleteAction` and `BulkUpdateAction` for safe mass delete and bulk update operations through the existing CRUD persistence pipeline.
- Added `bulkChunkSize()` to resources with a default chunk size of 100.
- Added bulk action documentation covering mass delete, bulk update, callback actions, permissions, chunk processing, and run-by-filter warnings.
- Added PHPUnit coverage for bulk results, empty selections, mass delete, bulk update, callback handlers, permissions, chunk processing, and `canRun` conditions.

### Changed
- Grid action panels now submit every configured bulk action through the same safe execution path instead of special-casing only bulk delete.

## v0.3.0 - 2026-05-14

### Added
- Added `DbOperationContext`, `DbResult`, `CrudPersister`, and `TransactionManager` for centralized ORM persistence and transactional CRUD operations.
- Added explicit `FormData` stages, field-level validation errors, conditional field helpers, lifecycle hooks, Bitrix CRUD events, and permission contexts.
- Added documentation for the saving pipeline, FormData stages, CrudPersister, transactions, lifecycle hooks, permissions, conditional validation, and ORM errors.
- Added PHPUnit coverage for persistence results, CRUD persisting, transactions, lifecycle hooks, FormData stages, conditional validation, and permissions.

### Changed
- CRUD create, update, delete, and mass delete now use the shared persister and transaction flow while preserving legacy lifecycle hook methods.
- Form saves now surface Bitrix ORM errors without treating failed `Result` objects as successful operations.

## v0.2.0 - 2026-05-13

### Added
- Added index query extension hooks to CRUD resources: `indexSelect`, `indexFilter`, `indexOrder`, `indexRuntime`, `beforeIndexQueryParams`, `afterIndexRows`, and `mapIndexRow`.
- Added ORM runtime field support for grids, including pass-through support for Bitrix `ReferenceField` instances.
- Added computed grid/detail fields via `Field::computed()` without automatically selecting computed columns from ORM.
- Added `Field::displayUsing()` for grid/detail presentation callbacks.
- Added filter ORM application API through `applyToOrmFilter()`.
- Added `CallbackFilter` for fully custom ORM filter logic.
- Added filter operators for text, number, select, and date filters.
- Added tests for query select/filter/order/runtime assembly, computed columns, display callbacks, callback filters, empty values, and row mapping.

### Changed
- Grid ORM query building now merges index field select, default/index select, UI/default/index filters, UI/default/index order, runtime fields, pagination, and resource parameter hooks in a deterministic order while preserving v0.1.0 hooks.
- Grid rows are now post-processed through resource row hooks before computed/display values are assembled.
- Empty filter values skip ORM filtering while preserving meaningful values like `0`, `'0'`, and `false`.


## v0.1.0 - 2026-05-13

### Added
- Added the initial Resource/CRUD skeleton for Bitrix admin pages.
- Added base Field, Filter, Action, Grid, and Page abstractions.
- Added initial README and PHPUnit smoke coverage for early CRUD/grid behavior.

## Unreleased
- Added foundational ORM-native relation layer primitives: `RelationMetadata`, `RelationType`, `OrmRelationResolver`, `ExplicitRelationResolver`, and `RuntimeRelationRegistrar` scaffolding for relation resolution/registration flow.
- Extended relation fields with relation DSL building blocks (`relation()`, explicit related/pivot keys, cascade flags) and added relation type declaration API for `BelongsTo`, `HasOne`, `HasMany`, `BelongsToMany`.
- Added BC-safe `BelongsToMany::storedAsCsv()` and save strategy toggles (`saveUsingOrm()`, `saveUsingManualSync()`) to prepare ORM-object and manual-sync modes.
- Added `DataManagerResource::queryObject()`, `findObject()`, and `usesEntityObjectForm()` default hook for object-graph form flow.

## Unreleased
- Improved ORM relation metadata detection and type validation in `OrmRelationResolver` (Reference/OneToMany/ManyToMany heuristics).
- Added runtime explicit relation builder/registrar wiring and validation for missing explicit config parts.
- Added ORM-aware `BelongsToMany` serialization mode: relation/pivot mode now keeps array IDs, legacy CSV mode remains supported.
- Added unit tests for relation resolver, runtime registrar, and BelongsToMany serialization behavior.

## Unreleased
- Migrated relation field classes to `MB\Bitrix\AdminKit\Field\Relation` namespace and updated internal imports.
- Reworked runtime relation builder/registrar to use Bitrix ORM relation field objects and explicit registration result.
- Added initial ORM relation services: `RelationValueLoader`, `RelationObjectMutator`, `OrmObjectRelationSynchronizer`, `ManualPivotSynchronizer`, and synchronizer interface.

## Unreleased
- Split PHPUnit suites into Unit/Integration in `phpunit.xml.dist` and excluded integration tests from Unit suite.
- Added `adminKitContext` and `eventModuleId` to `DbOperationContext`; FormPage post contexts now pass AdminKit context and fallback event module id (`main`).
- Updated DataManager event dispatch to resolve event module id from operation context with safe fallback to `main` (removed hardcoded `mb.bitrix.adminkit`).
- Added DataManagerResource relation sync extension point via `relationSyncStrategies()` and `getRelationSyncStrategies()`; EntityObjectFormSaver now registers user strategies before sync.
- Marked internal FormPage bridge/state mutator methods with `@internal` to reduce accidental public API surface.

## Unreleased
- Hardened docs markdown formatting for stage 1–2 core files and refreshed API-oriented structure for Fields/Filters documentation.
- Reworked `docs/fields.md` into a central Field overview with context matrix, fluent API matrix, catalog, relation section, and custom field guidance.
- Expanded `docs/user/reference/filters.md` with real filter classes/methods (`TextFilter`, `SelectFilter`, `NumberFilter`, `DateFilter`, `CheckboxFilter`, `CallbackFilter`) and ORM behavior notes.

## Unreleased
- Hardened markdown structure in Grid/Actions/Bulk actions docs (no one-line stubs; readable headings/lists/code blocks).
- Reworked top-level `docs/grid.md`, `docs/actions.md`, and `docs/bulk-actions.md` into full user-oriented sections with Bitrix-native grid/action-panel semantics.
- Added/updated practical guides and reference pages for Grid/Bulk/Actions: `docs/user/guides/grid.md`, `docs/user/guides/bulk-actions.md`, `docs/user/reference/actions.md`.
- Corrected docs-in-docs links to use proper relative paths from inside `docs/` and `docs/user/*` trees.
- Documented current BulkResult/error rendering behavior and explicit limitation for full UI error visualization.

## Unreleased
- Expanded `docs/user/cookbook` into task-oriented recipes (CRUD, grid columns, filters, row/bulk actions, SidePanel, pages, import/export, permissions, relation/custom fields) with API-verified examples and cross-links to guides/reference.
- Reworked `docs/user/cookbook/README.md` into a structured recipe map and removed stale/non-task-oriented cookbook navigation.
