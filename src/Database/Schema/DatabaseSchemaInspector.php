<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database\Schema;

use Bitrix\Main\Application;
use MB\Bitrix\AdminKit\Support\AdminCollection;

class DatabaseSchemaInspector
{
    public function __construct(private readonly ?object $connection = null)
    {
    }

    public function tableExists(string $tableName): bool
    {
        $connection = $this->connection();

        if (method_exists($connection, 'isTableExists')) {
            return (bool)$connection->isTableExists($tableName);
        }

        try {
            return $this->getColumns($tableName) !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    public function columnExists(string $tableName, string $columnName): bool
    {
        return array_key_exists($columnName, $this->getColumns($tableName));
    }

    /** @return array<string,array<string,mixed>> */
    public function getColumns(string $tableName): array
    {
        $connection = $this->connection();

        if (method_exists($connection, 'getTableFields')) {
            return $this->normalizeColumns($connection->getTableFields($tableName));
        }

        if (method_exists($connection, 'query')) {
            $result = $connection->query('SHOW COLUMNS FROM ' . $this->quoteIdentifier($tableName));
            return $this->normalizeColumnRows($this->fetchAll($result));
        }

        return [];
    }

    /** @return array<string,array{name:string,columns:array<int,string>,unique?:bool}> */
    public function getIndexes(string $tableName): array
    {
        $connection = $this->connection();

        if (method_exists($connection, 'getTableIndexes')) {
            return $this->normalizeIndexes($connection->getTableIndexes($tableName));
        }

        if (method_exists($connection, 'query')) {
            $result = $connection->query('SHOW INDEX FROM ' . $this->quoteIdentifier($tableName));
            return $this->normalizeIndexRows($this->fetchAll($result));
        }

        return [];
    }

    private function connection(): object
    {
        return $this->connection ?? Application::getConnection();
    }

    /** @param mixed $fields @return array<string,array<string,mixed>> */
    private function normalizeColumns(mixed $fields): array
    {
        $columns = [];
        foreach (AdminCollection::make(is_iterable($fields) ? $fields : [])->all() as $name => $field) {
            if (is_object($field)) {
                $columnName = method_exists($field, 'getName') ? (string)$field->getName() : (string)$name;
                $type = method_exists($field, 'getDataType') ? (string)$field->getDataType() : $this->readObjectProperty($field, 'type');
                $required = method_exists($field, 'isRequired') ? (bool)$field->isRequired() : false;
            } else {
                $columnName = is_string($name) ? $name : (string)($field['name'] ?? $field['Field'] ?? '');
                $type = (string)($field['type'] ?? $field['Type'] ?? '');
                $required = (bool)($field['required'] ?? false);
            }

            if ($columnName !== '') {
                $columns[$columnName] = ['name' => $columnName, 'type' => $type, 'required' => $required];
            }
        }

        return $columns;
    }

    /** @param array<int,array<string,mixed>> $rows @return array<string,array<string,mixed>> */
    private function normalizeColumnRows(array $rows): array
    {
        $columns = [];
        foreach ($rows as $row) {
            $name = (string)($row['Field'] ?? $row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $columns[$name] = [
                'name' => $name,
                'type' => (string)($row['Type'] ?? $row['type'] ?? ''),
                'required' => (($row['Null'] ?? '') === 'NO'),
            ];
        }

        return $columns;
    }

    /** @param mixed $indexes @return array<string,array{name:string,columns:array<int,string>,unique?:bool}> */
    private function normalizeIndexes(mixed $indexes): array
    {
        $normalized = [];
        foreach (AdminCollection::make(is_iterable($indexes) ? $indexes : [])->all() as $name => $index) {
            $indexName = is_string($name) ? $name : (string)($index['name'] ?? $index['Name'] ?? '');
            $columns = (array)($index['columns'] ?? $index['Columns'] ?? $index['fields'] ?? $index['Fields'] ?? []);
            if ($indexName !== '') {
                $normalized[$indexName] = ['name' => $indexName, 'columns' => array_values(array_map('strval', $columns))];
            }
        }

        return $normalized;
    }

    /** @param array<int,array<string,mixed>> $rows @return array<string,array{name:string,columns:array<int,string>,unique?:bool}> */
    private function normalizeIndexRows(array $rows): array
    {
        $indexes = [];
        foreach ($rows as $row) {
            $name = (string)($row['Key_name'] ?? $row['name'] ?? '');
            $column = (string)($row['Column_name'] ?? $row['column'] ?? '');
            if ($name === '' || $column === '') {
                continue;
            }
            $indexes[$name] ??= ['name' => $name, 'columns' => [], 'unique' => (($row['Non_unique'] ?? 1) === 0)];
            $indexes[$name]['columns'][] = $column;
        }

        return $indexes;
    }

    /** @return array<int,array<string,mixed>> */
    private function fetchAll(object $result): array
    {
        if (method_exists($result, 'fetchAll')) {
            return $result->fetchAll();
        }

        $rows = [];
        if (method_exists($result, 'fetch')) {
            while ($row = $result->fetch()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function readObjectProperty(object $object, string $property): string
    {
        return property_exists($object, $property) ? (string)$object->{$property} : '';
    }
}
