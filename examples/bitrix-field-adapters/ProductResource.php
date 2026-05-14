<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\EntitySelectorField;
use MB\Bitrix\AdminKit\Field\IblockElementSelectorField;
use MB\Bitrix\AdminKit\Field\UserSelectorField;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends CrudResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function formFields(): iterable
    {
        return [
            UserSelectorField::make('Responsible', 'RESPONSIBLE_ID'),
            EntitySelectorField::make('Company', 'COMPANY_ID')->entityId('company'),
            IblockElementSelectorField::make('Related element', 'ELEMENT_ID')->iblockId(5),
        ];
    }

    public function indexFields(): iterable
    {
        // TODO: Implement indexFields() method.
    }
}
