<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class FieldReadonlyConditionTest extends TestCase
{
    public function testReadonlyOnUpdateIsFalseOnCreate(): void
    {
        $field = Text::make('Title', 'NAME')->readonlyOnUpdate();

        self::assertFalse($field->isReadOnlyFor(['_mode' => 'create', '_id' => '']));
        self::assertTrue($field->isReadOnlyFor(['_mode' => 'edit', '_id' => '5']));
    }

    public function testReadonlyOnCreateIsFalseOnEdit(): void
    {
        $field = Text::make('Title', 'NAME')->readonlyOnCreate();

        self::assertTrue($field->isReadOnlyFor(['_mode' => 'create']));
        self::assertFalse($field->isReadOnlyFor(['_mode' => 'edit', 'ID' => '3']));
    }

    public function testBelongsToLinkRendersHiddenInputWhenWritableOnCreate(): void
    {
        $field = BelongsTo::make('Iblock', 'IBLOCK_ID', ProductTable::class)
            ->default(8)
            ->asLink()
            ->readonlyOnUpdate();

        $html = $field->renderFormField(null);

        self::assertStringContainsString('type="hidden"', $html);
        self::assertStringContainsString('name="IBLOCK_ID"', $html);
        self::assertStringContainsString('value="8"', $html);
    }

    public function testBelongsToLinkSkipsHiddenInputWhenReadonlyOnUpdate(): void
    {
        $field = BelongsTo::make('Iblock', 'IBLOCK_ID', ProductTable::class)
            ->default(8)
            ->asLink()
            ->readonlyOnUpdate();

        self::assertFalse($field->isReadOnlyFor(['_mode' => 'create']));
        self::assertTrue($field->isReadOnlyFor(['_mode' => 'edit', '_id' => '2']));

        $method = new \ReflectionMethod(BelongsTo::class, 'renderLinkField');
        $method->setAccessible(true);
        $html = $method->invoke($field, 8, ['_mode' => 'edit', '_id' => '2']);

        self::assertStringNotContainsString('type="hidden"', $html);
    }
}
