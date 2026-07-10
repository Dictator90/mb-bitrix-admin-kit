<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support\Fixtures;

/**
 * A stand-in DataManager whose UserField entity id does not exist, so
 * {@see \MB\Bitrix\AdminKit\Support\UserFieldFileColumns::forDataManager()}
 * exercises the UserFieldTable lookup and resolves to an empty column map.
 */
final class NonUfDataManagerStub
{
    public static function getUfId(): string
    {
        return 'ADMINKIT_NONEXISTENT_ENTITY_XYZ';
    }
}
