<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use LogicException;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
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

    public function queryObject(): mixed
    {
        $class = $this->getDataManagerClass();

        return $class::query();
    }

    public function findObject(mixed $id): mixed
    {
        $class = $this->getDataManagerClass();

        return $class::query()
            ->setSelect(["*"])
            ->where($this->getPrimaryKey(), $id)
            ->fetchObject();
    }

    public function usesEntityObjectForm(): bool
    {
        return false;
    }
}

