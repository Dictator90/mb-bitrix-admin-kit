<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\BelongsTo;
use MB\Bitrix\AdminKit\Field\EntitySelect;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class GridEditableMetadataTest extends TestCase
{
    public function testReadonlyFieldDisablesInlineEditMetadata(): void
    {
        $config = Text::make('Name', 'NAME')->editable()->readonly()->getGridColumnConfig();

        self::assertFalse($config['editable']);
    }

    public function testSelectInlineEditableProvidesItemsOnlyWhenEditableAndNotReadonly(): void
    {
        $editable = Select::make('Status', 'STATUS')
            ->options(['N' => 'New', 'P' => 'Published'])
            ->editable()
            ->getGridColumnConfig();

        self::assertSame('list', $editable['type']);
        self::assertSame(['items' => ['N' => 'New', 'P' => 'Published']], $editable['editable']);

        $readonly = Select::make('Status', 'STATUS')
            ->options(['N' => 'New'])
            ->editable()
            ->readonly()
            ->getGridColumnConfig();

        self::assertFalse($readonly['editable']);
    }

    public function testEntityAndRelationFieldsStayNonEditableInGrid(): void
    {
        $entity = EntitySelect::make('User', 'USER_ID')->editable()->getGridColumnConfig();
        $relation = BelongsTo::make('Category', 'CATEGORY_ID')->editable()->getGridColumnConfig();

        self::assertFalse($entity['editable']);
        self::assertFalse($relation['editable']);
    }
}
