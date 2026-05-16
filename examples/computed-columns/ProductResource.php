<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Name', 'NAME'),
            Text::make('Status', 'STATUS_LABEL')
                ->computed(static fn (array $row): string => ($row['ACTIVE'] ?? 'N') === 'Y' ? 'Active' : 'Inactive'),
        ];
    }

    public function formFields(): iterable
    {
        // TODO: Implement formFields() method.
    }
}
