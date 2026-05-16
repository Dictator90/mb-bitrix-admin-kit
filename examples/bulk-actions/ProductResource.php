<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function bulkActions(): iterable
    {
        return [
            BulkAction::make('deactivate', 'Deactivate selected')->update(['ACTIVE' => 'N']),
            MassDeleteAction::make()->confirm('Delete selected products?'),
        ];
    }

    public function bulkChunkSize(): int
    {
        return 100;
    }

    public function indexFields(): iterable
    {
        // TODO: Implement indexFields() method.
    }

    public function formFields(): iterable
    {
        // TODO: Implement formFields() method.
    }
}
