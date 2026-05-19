<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

/**
 * Ensures mediatorReferences() order matches owner (local) and related (remote) entities.
 *
 * Example: on GroupTable, ('USER', 'GROUP') is reordered to ('GROUP', 'USER').
 */
final class MediatorReferenceOrientation
{
    /**
     * @return array{0: string, 1: string} [localReference, remoteReference] on mediator entity.
     */
    public static function orient(
        string $mediatorDataManagerClass,
        string $ownerDataManagerClass,
        string $relatedDataManagerClass,
        string $firstReference,
        string $secondReference,
    ): array {
        if (
            $mediatorDataManagerClass === ''
            || $ownerDataManagerClass === ''
            || $relatedDataManagerClass === ''
            || $firstReference === ''
            || $secondReference === ''
        ) {
            return [$firstReference, $secondReference];
        }

        $firstTarget = self::referenceTargetDataManager($mediatorDataManagerClass, $firstReference);
        $secondTarget = self::referenceTargetDataManager($mediatorDataManagerClass, $secondReference);

        if ($firstTarget === $ownerDataManagerClass && $secondTarget === $relatedDataManagerClass) {
            return [$firstReference, $secondReference];
        }

        if ($secondTarget === $ownerDataManagerClass && $firstTarget === $relatedDataManagerClass) {
            return [$secondReference, $firstReference];
        }

        return [$firstReference, $secondReference];
    }

    private static function referenceTargetDataManager(string $mediatorDataManagerClass, string $referenceName): ?string
    {
        if (!method_exists($mediatorDataManagerClass, 'getEntity')) {
            return null;
        }

        $entity = $mediatorDataManagerClass::getEntity();
        if (!is_object($entity) || !method_exists($entity, 'hasField') || !$entity->hasField($referenceName)) {
            return null;
        }

        $field = $entity->getField($referenceName);
        if (!method_exists($field, 'getRefEntity')) {
            return null;
        }

        $refEntity = $field->getRefEntity();
        if (!is_object($refEntity) || !method_exists($refEntity, 'getDataClass')) {
            return null;
        }

        return (string) $refEntity->getDataClass();
    }
}
