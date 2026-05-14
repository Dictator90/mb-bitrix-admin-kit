<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use MB\Bitrix\AdminKit\Resource\SchemaAwareResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends CrudResource implements SchemaAwareResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function databaseTableName(): string
    {
        return 'vendor_demo_product';
    }

    public function expectedTableSchema(): TableSchema
    {
        return TableSchema::make($this->databaseTableName())
            ->column('ID', 'int', false)
            ->column('NAME', 'varchar', false)
            ->column('ACTIVE', 'char', false)
            ->index('PRIMARY', ['ID']);
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
