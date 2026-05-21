<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use InvalidArgumentException;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use RuntimeException;

final class ManualPivotSynchronizer implements RelationSynchronizerInterface
{
    public function sync(
        object $owner,
        RelationField $field,
        RelationMetadata $metadata,
        mixed $value,
        DbOperationContext $context,
    ): void {
        if (!$field instanceof BelongsToMany) {
            throw new InvalidArgumentException('Manual pivot sync supports BelongsToMany fields only.');
        }

        $pivotTableClass = $metadata->mediatorEntity;
        $ownerColumn = $metadata->foreignPivotKey;
        $relatedColumn = $metadata->relatedPivotKey;

        if ($pivotTableClass === null || $pivotTableClass === '') {
            throw new RuntimeException('Manual pivot sync requires pivotTable() / mediatorEntity metadata.');
        }

        if ($ownerColumn === null || $ownerColumn === '' || $relatedColumn === null || $relatedColumn === '') {
            throw new RuntimeException(
                'Manual pivot sync requires foreignPivotKey() and relatedPivotKey() metadata.',
            );
        }

        $ownerId = $this->extractOwnerId($owner, $metadata->ownerKey);
        if ($ownerId === null || $ownerId === '') {
            throw new RuntimeException('Manual pivot sync requires persisted owner primary key.');
        }

        $incomingIds = is_array($value)
            ? array_values(array_filter(array_map('strval', $value), static fn (string $id): bool => $id !== ''))
            : [];

        $this->syncPivotRows($pivotTableClass, $ownerColumn, $ownerId, $relatedColumn, $incomingIds);
    }

    /** @param list<string|int> $incomingIds */
    public function syncPivotRows(
        string $pivotTableClass,
        string $ownerColumn,
        mixed $ownerId,
        string $relatedColumn,
        array $incomingIds,
    ): void {
        $current = [];
        $result = $pivotTableClass::getList(['select' => [$relatedColumn], 'filter' => [$ownerColumn => $ownerId]]);
        while ($row = $result->fetch()) {
            $current[] = (string) $row[$relatedColumn];
        }

        $incoming = array_values(array_unique(array_map('strval', $incomingIds)));
        $toDelete = array_diff($current, $incoming);
        $toInsert = array_diff($incoming, $current);

        foreach ($toDelete as $id) {
            $pivotTableClass::delete([$ownerColumn => $ownerId, $relatedColumn => $id]);
        }

        foreach ($toInsert as $id) {
            $pivotTableClass::add([$ownerColumn => $ownerId, $relatedColumn => $id]);
        }
    }

    private function extractOwnerId(object $owner, string $ownerKey): mixed
    {
        if (method_exists($owner, 'getId')) {
            $id = $owner->getId();
            if ($id !== null && $id !== '') {
                return $id;
            }
        }

        if (method_exists($owner, 'get')) {
            return $owner->get($ownerKey);
        }

        return null;
    }
}
