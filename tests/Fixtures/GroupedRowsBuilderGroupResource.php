<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;

final class GroupedRowsBuilderGroupResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return GroupedRowsBuilderGroupTable::class;
    }

    public static function getId(): string
    {
        return 'groups';
    }

    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }
}
