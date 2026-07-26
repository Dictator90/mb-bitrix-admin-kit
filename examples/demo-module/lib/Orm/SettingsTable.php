<?php

declare(strict_types=1);

namespace Vendor\Demo\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Copy this table together with SettingsResource when adding the demo settings
 * section to another module. Its schema is created in install/index.php.
 */
final class SettingsTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_demo_settings';
    }

    public static function getMap(): array
    {
        return [
            (new Entity\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new Entity\StringField('CODE'))->configureRequired(),
            (new Entity\StringField('NAME'))->configureRequired(),
            new Entity\StringField('SCOPE'),
            new TextField('VALUE'),
            new Entity\StringField('ACTIVE'),
            new Entity\IntegerField('SORT'),
        ];
    }
}
