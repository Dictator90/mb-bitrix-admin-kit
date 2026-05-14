<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Schema;

use MB\Bitrix\AdminKit\Support\AdminCollection;

final class TableHealthCheck
{
    public function __construct(private readonly DatabaseSchemaInspector $inspector) {}

    /** @return array{table:string,exists:bool,missingColumns:array<int,string>,missingIndexes:array<int,string>,typeMismatches:array<string,array{expected:string,actual:string}>,status:string} */
    public function check(TableSchema $schema): array
    {
        $table = $schema->tableName();
        $exists = $this->inspector->tableExists($table);
        $actualColumns = $exists ? $this->inspector->getColumns($table) : [];
        $actualIndexes = $exists ? $this->inspector->getIndexes($table) : [];

        $missingColumns = [];
        $typeMismatches = [];
        foreach (AdminCollection::make($schema->requiredColumns())->all() as $name => $column) {
            if (!array_key_exists($name, $actualColumns)) {
                $missingColumns[] = $name;
                continue;
            }

            $actualType = (string)($actualColumns[$name]['type'] ?? '');
            if ($actualType !== '' && !$this->typesCompatible($column['type'], $actualType)) {
                $typeMismatches[$name] = ['expected' => $column['type'], 'actual' => $actualType];
            }
        }

        $missingIndexes = [];
        foreach (AdminCollection::make($schema->indexes())->all() as $name => $index) {
            if (!array_key_exists($name, $actualIndexes)) {
                $missingIndexes[] = $name;
                continue;
            }

            $expectedColumns = array_map('strtoupper', $index['columns']);
            $actualIndexColumns = array_map('strtoupper', $actualIndexes[$name]['columns'] ?? []);
            if ($expectedColumns !== [] && array_slice($actualIndexColumns, 0, count($expectedColumns)) !== $expectedColumns) {
                $missingIndexes[] = $name;
            }
        }

        $status = $exists && $missingColumns === [] && $missingIndexes === [] && $typeMismatches === [] ? 'ok' : 'warning';
        if (!$exists) {
            $status = 'missing_table';
        }

        return [
            'table' => $table,
            'exists' => $exists,
            'missingColumns' => $missingColumns,
            'missingIndexes' => $missingIndexes,
            'typeMismatches' => $typeMismatches,
            'status' => $status,
        ];
    }

    private function typesCompatible(string $expected, string $actual): bool
    {
        $expected = $this->normalizeType($expected);
        $actual = $this->normalizeType($actual);

        return $expected === '' || $actual === '' || $expected === $actual;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower($type);
        return match (true) {
            str_contains($type, 'int') => 'int',
            str_contains($type, 'char'), str_contains($type, 'text'), str_contains($type, 'string') => 'string',
            str_contains($type, 'date'), str_contains($type, 'time') => 'datetime',
            str_contains($type, 'decimal'), str_contains($type, 'double'), str_contains($type, 'float') => 'float',
            default => $type,
        };
    }
}
