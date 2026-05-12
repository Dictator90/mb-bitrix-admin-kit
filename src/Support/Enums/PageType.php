<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support\Enums;

enum PageType: string
{
    case INDEX = 'index';
    case FORM = 'form';
    case DETAIL = 'detail';
    case OPTIONS = 'options';
}
