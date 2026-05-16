<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use Bitrix\Main\ORM\Data\DataManager;

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
        return $this->dataManagerClass ?: null;
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
}
