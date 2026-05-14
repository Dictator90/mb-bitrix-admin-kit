# Import/export

AdminKit import/export is CSV-first for v1.0.0. XLSX/Excel engines are intentionally out of scope unless a future task explicitly adds them.

## Export safety

Export actions require explicit selected IDs or an allowed filter. Full export remains disabled unless a Resource/action opts in. Export must respect Resource permissions and field visibility.

## Import safety

Import validates upload input, parses CSV rows in chunks, maps columns to fields, and reuses `Form\DataPipeline` so CSV imports share Field normalization and validation with forms.

## Results

Import/export row sets, mappings, chunks, selected IDs, and errors should be stored in `AdminCollection` internally while public APIs expose simple arrays and iterables.
