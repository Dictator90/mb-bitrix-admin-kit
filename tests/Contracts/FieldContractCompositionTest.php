<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Contracts;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldExportContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldGridColumnContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldImportContract;
use MB\Bitrix\AdminKit\Contracts\Field\ReactiveFieldContract;
use MB\Bitrix\AdminKit\Field\EntitySelect;
use MB\Bitrix\AdminKit\Field\Field;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class FieldContractCompositionTest extends TestCase
{
    public function testFieldContractIsComposedFromFocusedContracts(): void
    {
        self::assertTrue(is_subclass_of(FieldContract::class, FieldGridColumnContract::class));
        self::assertTrue(is_subclass_of(FieldContract::class, FieldExportContract::class));
        self::assertTrue(is_subclass_of(FieldContract::class, FieldImportContract::class));
        self::assertTrue(is_subclass_of(FieldContract::class, ReactiveFieldContract::class));
    }

    public function testFieldBaseAndConcreteFieldsImplementFieldContract(): void
    {
        self::assertTrue(is_subclass_of(Field::class, FieldContract::class));
        self::assertInstanceOf(FieldContract::class, Text::make('Name', 'NAME'));
        self::assertInstanceOf(FieldContract::class, Select::make('Status', 'STATUS'));
        self::assertInstanceOf(FieldContract::class, EntitySelect::make('User', 'USER_ID'));
    }
}
