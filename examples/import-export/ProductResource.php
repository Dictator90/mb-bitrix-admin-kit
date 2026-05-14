<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends CrudResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [ID::make('ID'), Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')->required()];
    }

    public function allowExportByFilter(): bool
    {
        return true;
    }

    public function allowExportAll(): bool
    {
        return false;
    }

    public function maxImportRows(): int
    {
        return 1000;
    }
}
