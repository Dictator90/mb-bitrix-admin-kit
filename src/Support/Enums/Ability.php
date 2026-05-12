<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support\Enums;

enum Ability: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
}
