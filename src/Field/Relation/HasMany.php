<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Relation;

use MB\Bitrix\AdminKit\Relation\RelationType;

final class HasMany extends RelationField
{
    protected string $renderMode = 'table';

    /**
     * Preview table columns: column name => label (null = resolve from related ORM entity).
     *
     * @var array<string, string|null>|null
     */
    protected ?array $tableColumns = null;

    public function isToMany(): bool
    {
        return true;
    }

    public function relationDefault(): mixed
    {
        return [];
    }

    public function relationType(): RelationType
    {
        return RelationType::HAS_MANY;
    }

    /**
     * Render preview as a read-only table (ui.tilegrid).
     *
     * @param array<string, string|null>|list<string>|null $columns
     *        list: column names, labels from related ORM field titles;
     *        map: column => label (null or omit value for ORM title).
     *
     * @example ->asTable(['USER_ID', 'GROUP_ID'])
     * @example ->asTable(['USER_ID' => 'Пользователь', 'ID' => null])
     * @example ->asTable(['USER_ID' => 'Пользователь', 'ID']) — bare column after map (PHP int key)
     */
    public function asTable(array|null $columns = null): static
    {
        $this->renderMode = 'table';

        if ($columns !== null) {
            $this->configureTableColumns($columns);
        }

        return $this;
    }

    /**
     * @return array<string, string|null>|null
     */
    public function getTableColumns(): ?array
    {
        return $this->tableColumns;
    }

    /**
     * @return list<string>
     */
    public function getTablePreviewColumnNames(): array
    {
        if ($this->tableColumns === null || $this->tableColumns === []) {
            return [];
        }

        return array_values(array_keys($this->tableColumns));
    }

    public function asEmbeddedForm(): static
    {
        $this->renderMode = 'embedded';

        return $this;
    }

    public function asGrid(): static
    {
        $this->renderMode = 'grid';

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $values = is_array($value) ? $value : ($value === null ? [] : [$value]);

        return match ($this->renderMode) {
            'embedded' => $this->renderEmbeddedPreview($values),
            'grid' => $this->renderEmbeddedPreview($values),
            default => $this->renderTablePreview($values),
        };
    }

    /** @param array<int|string,mixed> $values */
    protected function renderTablePreview(array $values): string
    {
        return (new RelationTileGridPreviewRenderer())->render(
            $this->normalizePreviewRows($values),
            $this->getColumn(),
            $this->resolveTableColumnDefinitions(),
        );
    }

    /**
     * @param array<string, string|null>|list<string> $columns
     */
    protected function configureTableColumns(array $columns): void
    {
        $this->tableColumns = [];

        if ($columns === []) {
            return;
        }

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $name = is_string($value) ? $value : (string) $value;
                if ($name !== '') {
                    $this->tableColumns[$name] = null;
                }

                continue;
            }

            $name = is_string($key) ? $key : (string) $key;
            if ($name === '') {
                continue;
            }

            $this->tableColumns[$name] = is_string($value) && $value !== '' ? $value : null;
        }
    }

    /**
     * @return array<string, string>|null column => resolved label
     */
    protected function resolveTableColumnDefinitions(): ?array
    {
        if ($this->tableColumns === null || $this->tableColumns === []) {
            return null;
        }

        $dataManagerClass = $this->relatedTableClass ?? $this->tableClass;
        $definitions = [];

        foreach ($this->tableColumns as $column => $label) {
            $definitions[$column] = $label !== null
                ? RelationOrmFieldLabelResolver::normalizeTitle($label, $column)
                : RelationOrmFieldLabelResolver::resolve($dataManagerClass, $column);
        }

        return $definitions;
    }

    /** @param array<int|string,mixed> $values */
    protected function renderEmbeddedPreview(array $values): string
    {
        return '<div class="adminkit-relation-embedded-preview adminkit-relation-embedded-preview--limited">'
            . htmlspecialcharsbx('Embedded HasMany editing is limited; use object graph form mode for supported saves.')
            . '</div>';
    }

    /**
     * @param array<int|string,mixed> $values
     * @return list<array<string, mixed>>
     */
    protected function normalizePreviewRows(array $values): array
    {
        $rows = [];

        foreach ($values as $value) {
            $row = $this->normalizePreviewRow($value);
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizePreviewRow(mixed $row): array
    {
        if (is_array($row)) {
            return $this->filterScalarRow($row);
        }

        if (is_object($row) && method_exists($row, 'collectValues')) {
            $values = $row->collectValues();
            if (is_array($values)) {
                return $this->filterScalarRow($values);
            }
        }

        if (is_scalar($row) && $row !== '') {
            return ['VALUE' => $row];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function filterScalarRow(array $row): array
    {
        if ($this->tableColumns !== null && $this->tableColumns !== []) {
            $filtered = [];

            foreach (array_keys($this->tableColumns) as $column) {
                if (!array_key_exists($column, $row)) {
                    continue;
                }

                $value = $row[$column];
                if ($value === null || is_scalar($value)) {
                    $filtered[$column] = $value;
                    continue;
                }

                if (is_object($value) && method_exists($value, 'getId')) {
                    $relatedId = $value->getId();
                    if ($relatedId !== null && $relatedId !== '') {
                        $filtered[$column] = $relatedId;
                    }
                }
            }

            return $filtered;
        }

        $filtered = [];

        foreach ($row as $key => $value) {
            if (!is_string($key) && !is_int($key)) {
                continue;
            }

            $column = (string) $key;

            if ($value === null || is_scalar($value)) {
                $filtered[$column] = $value;
                continue;
            }

            if (is_object($value) && method_exists($value, 'getId')) {
                $relatedId = $value->getId();
                if ($relatedId !== null && $relatedId !== '') {
                    $filtered[$column] = $relatedId;
                }
            }
        }

        return $filtered;
    }
}
