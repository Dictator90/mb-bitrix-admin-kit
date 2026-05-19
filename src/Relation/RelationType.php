<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

enum RelationType: string
{
    case BELONGS_TO = 'belongs_to';
    case HAS_ONE = 'has_one';
    case HAS_MANY = 'has_many';
    case BELONGS_TO_MANY = 'belongs_to_many';
}
