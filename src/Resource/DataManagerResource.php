<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use LogicException;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Relation\RelationMetadataResolver;
use MB\Bitrix\AdminKit\Relation\RelationValueLoader;
use MB\Bitrix\AdminKit\Relation\Strategies\RelationSyncStrategyInterface;
use MB\Bitrix\AdminKit\Resource\Concerns\HasDataManager;
use MB\Bitrix\AdminKit\Resource\Concerns\HasDataManagerPersistence;

/**
 * Resource class for Bitrix D7 ORM-backed CRUD sections.
 *
 * Form saves always use Bitrix EntityObject persistence (see FormPage).
 *
 * @template T of DataManager
 * @extends CrudResource<T>
 */
abstract class DataManagerResource extends CrudResource implements DataManagerResourceContract
{
    /** @use HasDataManager<T> */
    use HasDataManager;
    use HasDataManagerPersistence;

    /** @return class-string<T> */
    abstract public function dataManagerClass(): string;

    public function getDataManagerClass(): ?string
    {
        $class = $this->dataManagerClass();
        if ($class === '') {
            throw new LogicException(static::class . ' must declare a non-empty dataManagerClass().');
        }

        return $class;
    }

    public function hasCrud(): bool
    {
        return true;
    }

    /**
     * @param list<string> $select
     */
    public function queryObject(array $select = ['*']): mixed
    {
        $class = $this->requireConfiguredDataManagerClass();
        if (!method_exists($class, 'query')) {
            throw new LogicException(static::class . ' DataManager must support query() for EntityObject persistence.');
        }

        /** @phpstan-ignore method.static */
        $query = $class::query();

        if ($select !== [] && $select !== ['*']) {
            $query->setSelect(array_values(array_unique($select)));
        }

        return $query;
    }

    /**
     * @param list<string> $select
     */
    public function findObject(mixed $id, array $select = ['*']): mixed
    {
        $this->assertSinglePrimaryKey();

        $primaryKey = $this->getPrimaryKey();

        return $this->queryObject($select)
            ->where($primaryKey, $id)
            ->fetchObject();
    }

    public function newObject(): object
    {
        $class = $this->requireConfiguredDataManagerClass();

        if (method_exists($class, 'createObject')) {
            $object = $class::createObject();
            if (is_object($object) && method_exists($object, 'set') && method_exists($object, 'save')) {
                return $object;
            }
        }

        $entity = $this->getEntity();
        if (method_exists($entity, 'createObject')) {
            $object = $entity->createObject();
            if (is_object($object) && method_exists($object, 'set') && method_exists($object, 'save')) {
                return $object;
            }
        }

        throw new LogicException(
            static::class . ' DataManager must support createObject() for EntityObject form persistence.',
        );
    }

    /**
     * @param iterable<FieldContract> $fields
     * @return list<string>
     */
    public function relationSelectForFields(iterable $fields): array
    {
        $relationFields = [];
        foreach ($fields as $field) {
            if ($field instanceof RelationField) {
                $relationFields[] = $field;
            }
        }

        $class = $this->getDataManagerClass();
        if ($class === null || $relationFields === []) {
            return ['*'];
        }

        return (new RelationMetadataResolver())->relationSelects($class, $relationFields);
    }

    public function resolveRelationValue(mixed $item, RelationField $field): mixed
    {
        $class = $this->getDataManagerClass();
        if ($class === null) {
            return null;
        }

        $metadata = (new RelationMetadataResolver())->resolve($class, $field);
        if ($metadata === null) {
            return is_array($item) ? ($item[$field->getColumn()] ?? null) : null;
        }

        return (new RelationValueLoader())->load($item, $field, $metadata);
    }

    /**
     * User-defined relation synchronization strategies.
     * They are registered before built-in strategies and have higher priority.
     *
     * @return iterable<RelationSyncStrategyInterface>
     */
    protected function relationSyncStrategies(): iterable
    {
        return [];
    }

    /** @return iterable<RelationSyncStrategyInterface> */
    final public function getRelationSyncStrategies(): iterable
    {
        return $this->relationSyncStrategies();
    }

    protected function assertSinglePrimaryKey(): void
    {
        $entity = $this->getEntity();
        if (!method_exists($entity, 'getPrimaryArray')) {
            return;
        }

        $primary = $entity->getPrimaryArray();
        if (!is_array($primary)) {
            return;
        }

        if (count($primary) > 1) {
            throw new LogicException(
                static::class . ' does not support composite primary keys in EntityObject form persistence.',
            );
        }
    }

    /** @return class-string<T> */
    private function requireConfiguredDataManagerClass(): string
    {
        $class = $this->getDataManagerClass();
        if ($class === null || $class === '') {
            throw new LogicException(static::class . ' must declare a non-empty dataManagerClass().');
        }

        return $class;
    }
}
