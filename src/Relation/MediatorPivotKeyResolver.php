<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

/**
 * Resolves scalar pivot column names from Reference field names on a mediator entity.
 *
 * Example: UserGroupTable with mediatorReferences('USER', 'GROUP') → USER_ID, GROUP_ID.
 */
final class MediatorPivotKeyResolver
{
    /**
     * @return array{0: string, 1: string}|null Local (owner) and remote (related) pivot column names.
     */
    public static function resolve(
        string $mediatorDataManagerClass,
        string $localMediatorReference,
        string $remoteMediatorReference,
    ): ?array {
        if (
            $mediatorDataManagerClass === ''
            || $localMediatorReference === ''
            || $remoteMediatorReference === ''
            || !method_exists($mediatorDataManagerClass, 'getEntity')
        ) {
            return null;
        }

        $entity = $mediatorDataManagerClass::getEntity();
        if (!is_object($entity) || !method_exists($entity, 'hasField') || !method_exists($entity, 'getField')) {
            return null;
        }

        $localColumn = self::resolvePivotColumn($entity, $localMediatorReference);
        $remoteColumn = self::resolvePivotColumn($entity, $remoteMediatorReference);

        if ($localColumn === null || $remoteColumn === null) {
            return null;
        }

        return [$localColumn, $remoteColumn];
    }

    private static function resolvePivotColumn(object $entity, string $referenceName): ?string
    {
        if (!$entity->hasField($referenceName)) {
            return null;
        }

        $field = $entity->getField($referenceName);
        if (!method_exists($field, 'getElementals')) {
            return null;
        }

        $elementals = $field->getElementals();
        if (!is_array($elementals) || $elementals === []) {
            return null;
        }

        return (string) array_key_first($elementals);
    }
}
