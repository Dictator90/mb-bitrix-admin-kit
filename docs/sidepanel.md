# SidePanel v0.8.0

Resources can opt into SidePanel behavior:

- `useSidePanel(): bool`
- `createInSidePanel(): bool`
- `editInSidePanel(): bool`
- `detailInSidePanel(): bool`
- `sidePanelWidth(): int`

`SidePanelAdapter` adds `IFRAME=Y`, opens create/edit/detail sliders, can reload the grid on close, and emits a close-after-save script for iframe mode without breaking full-page rendering.
