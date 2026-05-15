# Resource import (library only — UI disabled)

> **Import UI and index-page flow are temporarily disabled.** Do not expect toolbar import buttons, SidePanel import wizards, or `action=import` handling on `IndexPage` in the current branch. The notes below describe the **library layer** kept for a future re-enable.

v0.7.0 added a CSV-first import layer for CRUD resources. XLSX/Excel import is out of scope.

## Components (not active on IndexPage)

- `ImportAction` — parse, preview, validate-only, and import flows.
- `ImportContext` — resource, rows, mapping, mode, key field, limits.
- `CsvImporter` — parses CSV, maps columns, validates via `Form\DataPipeline`, persists rows.

## CSV format

The first row is headers. Values map to resource field columns before validation.

## Modes (when re-enabled)

- `create` — create every valid row.
- `update` — update by `keyField`.
- `upsert` — update or create by `keyField`.

## Field pipeline

Import is designed to use `Form\DataPipeline` so CSV rows share Field `normalize()` and validation with form saves.

## Permissions and limits

- Create imports require `canCreate()`; update imports require `canUpdate()` per row.
- Row limits via `maxImportRows()` on Resource (when import is restored).

For export, see `docs/import-export.md`.
