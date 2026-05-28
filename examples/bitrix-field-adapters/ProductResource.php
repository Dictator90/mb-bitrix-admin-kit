<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\EntitySelect;
use MB\Bitrix\AdminKit\Field\IblockElementSelect;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\UserSelect;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function formFields(): iterable
    {
        return [
            UserSelect::make('Responsible', 'RESPONSIBLE_ID'),
            EntitySelect::make('Company', 'COMPANY_ID')->entityId('company'),
            IblockElementSelect::make('Related element', 'ELEMENT_ID')->iblockId(5),
        ];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Name', 'NAME'),
            Text::make('Responsible', 'RESPONSIBLE_ID'),
        ];
    }
}
