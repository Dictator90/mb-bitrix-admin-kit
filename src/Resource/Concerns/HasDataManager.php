<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use Bitrix\Main\ORM\Data\DataManager;
use LogicException;

/**
 * @template T of DataManager
 */
trait HasDataManager
{
    /** @var class-string<T>|null */
    protected ?string $dataManagerClass = null;

    protected string $primaryKey = 'ID';

    /** @return class-string<T>|null */
    public function getDataManagerClass(): ?string
    {
        if ($this->dataManagerClass !== null && $this->dataManagerClass !== '') {
            return $this->dataManagerClass;
        }

        if (method_exists($this, 'dataManagerClass')) {
            $class = (string)$this->dataManagerClass();

            return $class !== '' ? $class : null;
        }

        return null;
    }

    public function dataManagerClass(): string
    {
        return $this->dataManagerClass ?? '';
    }

    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function hasCrud(): bool
    {
        return $this->getDataManagerClass() !== null;
    }

    public function databaseTableName(): string
    {
        $class = $this->getDataManagerClass();
        if ($class && method_exists($class, 'getTableName')) {
            return (string)$class::getTableName();
        }

        return '';
    }

    /**
     * Returns Bitrix ORM entity for the configured DataManager class.
     */
    public function getEntity(): object
    {
        $class = $this->getDataManagerClass();
        if ($class === null || $class === '') {
            throw new LogicException(static::class . ' must declare a non-empty dataManagerClass().');
        }

        if (!method_exists($class, 'getEntity')) {
            throw new LogicException(
                sprintf('%s DataManager %s must implement getEntity().', static::class, $class),
            );
        }

        $entity = $class::getEntity();
        if (!is_object($entity)) {
            throw new LogicException(
                sprintf('%s::getEntity() must return an object.', $class),
            );
        }

        return $entity;
    }
}
