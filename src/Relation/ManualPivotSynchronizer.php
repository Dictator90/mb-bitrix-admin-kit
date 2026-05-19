<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

final class ManualPivotSynchronizer
{
    /** @param list<string|int> $incomingIds */
    public function sync(string $pivotTableClass, string $ownerColumn, mixed $ownerId, string $relatedColumn, array $incomingIds): void
    {
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
}
