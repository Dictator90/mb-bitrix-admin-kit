# HtmlEditor

Класс: `MB\Bitrix\AdminKit\Field\HtmlEditor`.

Назначение: HTML-контент с визуальным редактором Bitrix (`CHTMLEditor`) и fallback на textarea (`Textarea`).

> `Field\Html` — deprecated alias для `HtmlEditor`.

## Размер и режим

- `rows(int $rows)` — число строк textarea / базовая высота редактора (если `height()` не задан).
- `height(int $pixels)` — явная высота редактора в px.
- `width(int|string $width)` — ширина редактора (`800` или `'100%'`).
- `disableEditor(bool $disable = true)` — отключает визуальный редактор, оставляет textarea.
- `view('wysiwyg'|'code'|'split')` — начальный режим просмотра.
- `bbCode(bool $enable = true)` — BBCode-режим вместо HTML.

## PHP и безопасность

- `allowPhp(bool $allow = true)` — разрешить PHP в редакторе (по умолчанию выключено).
- `limitPhpAccess(bool $limit = true)` — ограничить доступ к PHP в редакторе.

## Панели и возможности

- `showTaskbar(bool $show = true)` — боковая панель (компоненты/сниппеты).
- `showComponents(bool $show = true)` — вкладка компонентов в taskbar.
- `showSnippets(bool $show = true)` — сниппеты в taskbar.
- `useFileDialogs(bool $use = true)` — диалоги выбора файлов/медиа.
- `showNodeNavi(bool $show = true)` — навигация по DOM-узлам.
- `uploadImagesFromClipboard(bool $enable = true)` — вставка изображений из буфера обмена.

## UX

- `placeholder(?string $text)` — placeholder (наследуется от `Field`, используется и в textarea, и в редакторе).
- `editorPlaceholder(string $text)` — placeholder только для `CHTMLEditor` (перекрывает `placeholder()`).
- `lazyLoad(bool $lazy = true)` — отложенная инициализация.
- `askBeforeUnload(bool $ask = true)` — предупреждение при уходе со страницы с несохранёнными изменениями.
- `beforeUnloadMessage(string $message)` — текст предупреждения.
- `setFocusOnShow(bool $focus = true)` — фокус при показе редактора.
- `autoResize(bool $enable = true, ?int $maxHeight = null, ?int $offset = null)` — автоизменение высоты.
- `autoResizeSaveSize(bool $save = true)` — сохранять размер после autoResize.
- `autoLink(bool $enable = true)` — автолинковка URL.
- `hiddenUntilInit(bool $hidden = true)` — скрыть DOM редактора до инициализации JS.

## Сайт и шаблон

- `siteId(string $siteId)` — ID сайта для шаблонов редактора.
- `templateId(string $templateId)` — ID шаблона сайта.
- `relPath(string $path)` — относительный путь для fileman.

## Внешний вид

- `fontSize(string $size)` — размер шрифта в iframe (`'14px'`).
- `iframeCss(string $css)` — дополнительный CSS для iframe редактора.
- `minBodySize(?int $width = null, ?int $height = null)` — минимальные размеры body.
- `normalBodyWidth(int $width)` — нормальная ширина body.

## Расширенное

- `editorId(string $id)` — свой ID редактора (по умолчанию генерируется из имени поля).
- `controlsMap(array $controls)` — конфиг кнопок тулбара (формат `CHTMLEditor`).
- `componentFilter(array $filter)` — фильтр компонентов в taskbar.

Пример:
```php
HtmlEditor::make('Контент', 'HTML')
    ->height(420)
    ->width('100%')
    ->editorPlaceholder('Введите HTML...')
    ->showTaskbar()
    ->showSnippets()
    ->autoResize(true, 900)
    ->uploadImagesFromClipboard();
```

## Значения по умолчанию

- `rows = 15`
- `useEditor = true`
- taskbar/components/snippets — выключены (минимальный UI для форм настроек)
- `useFileDialogs = true`
- `allowPhp = false`
- `askBeforeUnloadPage = false` (не мешает сохранению формы AdminKit)
