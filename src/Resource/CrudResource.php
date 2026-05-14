<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;

/**
 * Explicit base class for Bitrix D7 ORM-backed CRUD resources.
 *
 * @template T of DataManager
 * @extends Resource<T>
 */
abstract class CrudResource extends Resource
{
    /** @return class-string<T> */
    public function dataManagerClass(): string
    {
        return parent::dataManagerClass();
    }

    public function getDataManagerClass(): ?string
    {
        return $this->dataManagerClass ?: $this->dataManagerClass();
    }

    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey();
    }

    public function bulkChunkSize(): int
    {
        return 100;
    }

    /** @return iterable<FieldContract> */
    abstract public function indexFields(): iterable;

    /** @return iterable<FieldContract> */
    abstract public function formFields(): iterable;

    /** @return iterable<FieldContract> */
    public function detailFields(): iterable
    {
        return $this->formFields();
    }

    /** @return iterable<FilterContract> */
    public function filters(): iterable { return []; }

    /** @return iterable<ActionContract> */
    public function rowActions(): iterable { return []; }

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable { return []; }
}
