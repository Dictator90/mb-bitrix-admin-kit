<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;

interface DataManagerResourceContract extends
    ResourceContract,
    CrudResourceContract,
    ResourceOrmContract,
    ResourcePersistenceContract
{
    /**
     * @param list<string> $select
     */
    public function queryObject(array $select = ['*']): mixed;

    /**
     * @param list<string> $select
     */
    public function findObject(mixed $id, array $select = ['*']): mixed;

    public function newObject(): object;

    /**
     * Bitrix D7 ORM entity for {@see getDataManagerClass()} ({@see DataManager::getEntity()}).
     */
    public function getEntity(): object;

    /**
     * @param iterable<FieldContract> $fields
     * @return list<string>
     */
    public function relationSelectForFields(iterable $fields): array;

    public function resolveRelationValue(mixed $item, RelationField $field): mixed;
}
