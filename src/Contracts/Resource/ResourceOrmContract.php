<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

interface ResourceOrmContract
{
    /** @return class-string|null */
    public function getDataManagerClass(): ?string;

    /** @return class-string */
    public function dataManagerClass(): string;

    public function getPrimaryKey(): string;

    public function primaryKey(): string;

    public function databaseTableName(): string;

    public function hasCrud(): bool;
}
