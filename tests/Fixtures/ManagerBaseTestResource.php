<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\CrudResource;

abstract class ManagerBaseTestResource extends CrudResource
{
    protected string $title = 'Base';

    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}
