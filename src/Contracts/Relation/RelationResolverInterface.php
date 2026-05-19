<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Relation;

use MB\Bitrix\AdminKit\Field\Relation\RelationField;
use MB\Bitrix\AdminKit\Relation\RelationMetadata;

interface RelationResolverInterface
{
    public function resolve(string $ownerDataManagerClass, RelationField $field): ?RelationMetadata;
}
