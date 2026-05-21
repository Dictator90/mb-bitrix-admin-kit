<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class ProductOrmEntity
{
    /** @return list<string> */
    public function getPrimaryArray(): array
    {
        return ['ID'];
    }

    public function createObject(): ProductOrmEntityObject
    {
        return new ProductOrmEntityObject(['ID' => null, 'NAME' => '']);
    }
}
