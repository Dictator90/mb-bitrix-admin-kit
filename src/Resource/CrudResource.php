<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use Bitrix\Main\ORM\Data\DataManager;
use LogicException;

/**
 * Explicit base class for Bitrix D7 ORM-backed CRUD resources.
 *
 * New ORM CRUD sections should extend this class. {@see Resource} remains the
 * backward-compatible base and still exposes CRUD helpers for legacy resources
 * that extend Resource directly.
 *
 * @template T of DataManager
 * @extends Resource<T>
 */
abstract class CrudResource extends Resource
{
    public function dataManagerClass(): string
    {
        $class = parent::dataManagerClass();
        if ($class === '') {
            throw new LogicException(static::class . ' must declare a non-empty dataManagerClass().');
        }

        return $class;
    }

    public function getDataManagerClass(): ?string
    {
        return $this->dataManagerClass();
    }

    public function hasCrud(): bool
    {
        return true;
    }
}
