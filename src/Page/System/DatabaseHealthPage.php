<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\System;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Database\Schema\DatabaseSchemaInspector;
use MB\Bitrix\AdminKit\Database\Schema\TableHealthCheck;
use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use MB\Bitrix\AdminKit\Resource\SchemaAwareResource;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class DatabaseHealthPage
{
    /** @param iterable<ResourceContract> $resources */
    public function __construct(
        private readonly iterable $resources,
        private readonly ?DatabaseSchemaInspector $inspector = null,
    ) {}

    /** @return array<int,array<string,mixed>> */
    public function diagnostics(): array
    {
        $checker = new TableHealthCheck($this->inspector ?? new DatabaseSchemaInspector());
        $rows = [];

        foreach (AdminCollection::make($this->resources)->all() as $resource) {
            if (!$resource instanceof ResourceContract) {
                continue;
            }

            $dataManagerClass = $resource->getDataManagerClass();
            $tableName = $resource->databaseTableName();
            $schema = $resource instanceof SchemaAwareResource
                ? $resource->expectedTableSchema()
                : ($tableName !== '' ? TableSchema::make($tableName) : null);
            $health = $schema ? $checker->check($schema) : [
                'table' => $tableName,
                'exists' => false,
                'missingColumns' => [],
                'missingIndexes' => [],
                'typeMismatches' => [],
                'status' => 'unknown',
            ];

            $rows[] = [
                'resource' => $resource::getId(),
                'title' => $resource->getTitle(),
                'dataManagerClass' => $dataManagerClass,
                'tableName' => $tableName,
                'tableExists' => $health['exists'],
                'missingColumns' => $health['missingColumns'],
                'missingIndexes' => $health['missingIndexes'],
                'typeMismatches' => $health['typeMismatches'],
                'status' => $health['status'],
            ];
        }

        return AdminCollection::make($rows)->all();
    }

    public function render(): void
    {
        echo '<div class="adminkit-database-health">';
        echo '<table class="adminkit-database-health__table">';
        echo '<thead><tr><th>Resource</th><th>DataManager</th><th>Table</th><th>Exists</th><th>Missing columns</th><th>Missing indexes</th><th>Status</th></tr></thead><tbody>';

        foreach ($this->diagnostics() as $row) {
            echo '<tr class="adminkit-database-health__row adminkit-database-health__row--' . htmlspecialcharsbx((string)$row['status']) . '">';
            echo '<td>' . htmlspecialcharsbx((string)$row['resource']) . '</td>';
            echo '<td>' . htmlspecialcharsbx((string)$row['dataManagerClass']) . '</td>';
            echo '<td>' . htmlspecialcharsbx((string)$row['tableName']) . '</td>';
            echo '<td>' . ((bool)$row['tableExists'] ? 'yes' : 'no') . '</td>';
            echo '<td>' . htmlspecialcharsbx(implode(', ', (array)$row['missingColumns'])) . '</td>';
            echo '<td>' . htmlspecialcharsbx(implode(', ', (array)$row['missingIndexes'])) . '</td>';
            echo '<td>' . htmlspecialcharsbx((string)$row['status']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }
}
