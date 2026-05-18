<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;

interface IndexPageContract extends CrudPageContract
{
    public function definition(): IndexPageDefinitionContract;
}
