<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Number;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends CrudResource
{
    protected string $title = 'Catalog products';

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Name', 'NAME'),
            Select::make('Type', 'TYPE')->options($this->types()),
            Number::make('Price', 'PRICE'),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Name', 'NAME')->required(),
            Select::make('Type', 'TYPE')->options($this->types())->default('simple'),
            Number::make('Price', 'PRICE')->required(),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y'),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Name', 'NAME'),
            SelectFilter::make('Type', 'TYPE')->options($this->types()),
        ];
    }

    public function rowActions(): iterable
    {
        return [RowAction::view(), RowAction::edit(), RowAction::delete()];
    }

    public function bulkActions(): iterable
    {
        return [
            BulkAction::make('activate', 'Activate')->update(['ACTIVE' => 'Y']),
            BulkAction::delete(),
        ];
    }

    private function types(): array
    {
        return ['simple' => 'Simple', 'service' => 'Service'];
    }
}
