<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource;

use MB\Bitrix\AdminKit\Database\Schema\TableSchema;

interface SchemaAwareResource
{
    public function expectedTableSchema(): TableSchema;
}
