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
use MB\Bitrix\AdminKit\Resource\Concerns\HasDataManager;
use MB\Bitrix\AdminKit\Resource\Concerns\HasDataManagerPersistence;

/**
 * Resource class for Bitrix D7 ORM-backed CRUD sections.
 *
 * @template T of DataManager
 * @extends CrudResource<T>
 */
abstract class DataManagerResource extends CrudResource implements DataManagerResourceContract
{
    /** @use HasDataManager<T> */
    use HasDataManager;
    use HasDataManagerPersistence;

    protected bool $entityObjectForm = false;

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

    public function enableEntityObjectForm(bool $enabled = true): static
    {
        $this->entityObjectForm = $enabled;

        return $this;
    }

    public function usesEntityObjectForm(): bool
    {
        return $this->entityObjectForm;
    }

    /**
     * @param list<string> $relations
     */
    public function queryObject(array $relations = []): mixed
    {
        $class = $this->getDataManagerClass();
        $query = $class::query();

        if ($relations !== []) {
            $query->setSelect(array_values(array_unique($relations)));
        }

        return $query;
    }

    /**
     * @param list<string> $relations
     */
    public function findObject(mixed $id, array $relations = []): mixed
    {
        $select = $relations === [] ? ['*'] : array_values(array_unique($relations));

        return $this->queryObject($select)
            ->where($this->getPrimaryKey(), $id)
            ->fetchObject();
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
}
