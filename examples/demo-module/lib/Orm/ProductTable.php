<?php

declare(strict_types=1);

namespace Vendor\Demo\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;

final class ProductTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_demo_product';
    }

    public static function getMap(): array
    {
        return [
            (new Entity\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new Entity\StringField('NAME'))->configureRequired(),
            new Entity\StringField('TYPE'),
            new Entity\StringField('ACTIVE'),
            new Entity\IntegerField('SORT'),
            new Entity\FloatField('PRICE'),
            new Entity\IntegerField('CREATED_BY'),
        ];
    }
}
