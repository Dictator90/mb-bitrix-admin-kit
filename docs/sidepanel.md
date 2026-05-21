# SidePanel v0.8.0

Ресурсы могут включить поведение SidePanel:

- `useSidePanel(): bool`
- `createInSidePanel(): bool`
- `editInSidePanel(): bool`
- `detailInSidePanel(): bool`
- `sidePanelWidth(): int`

`SidePanelAdapter` добавляет `IFRAME=Y`, открывает слайдеры create/edit/detail, может перезагружать грид при закрытии и выводит скрипт закрытия после сохранения для iframe-режима, не ломая полноэкранный рендер.
