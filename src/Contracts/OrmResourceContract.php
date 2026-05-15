<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface OrmResourceContract
{
    /**
     * @return class-string|null
     */
    public function getDataManagerClass(): ?string;

    /**
     * @return class-string
     */
    public function dataManagerClass(): string;

    public function getPrimaryKey(): string;

    public function primaryKey(): string;

    public function hasCrud(): bool;

    public function databaseTableName(): string;

    /**
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function getList(array $params = []): array;

    /**
     * @param array<string,mixed> $filter
     */
    public function getCount(array $filter = []): int;
}
