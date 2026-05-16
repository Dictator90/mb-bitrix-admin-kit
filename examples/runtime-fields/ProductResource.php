<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\CategoryTable;
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
            Text::make('Category', 'CATEGORY_NAME'),
        ];
    }

    public function indexSelect(GridContext $context): array
    {
        return ['ID', 'NAME', 'CATEGORY_NAME' => 'CATEGORY.NAME'];
    }

    public function indexRuntime(GridContext $context): array
    {
        return [
            new Reference('CATEGORY', CategoryTable::class, ['=this.CATEGORY_ID' => 'ref.ID']),
        ];
    }

    public function formFields(): iterable
    {
        // TODO: Implement formFields() method.
    }
}
