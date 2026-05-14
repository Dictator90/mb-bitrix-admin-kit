<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\EntitySelect;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\UserSelect;
use PHPUnit\Framework\TestCase;

final class FieldApiV050Test extends TestCase
{
    public function testCallableOptionsAndDisplayUsingWorkWithoutCollectionApi(): void
    {
        $field = Select::make('Status', 'STATUS')
            ->options(static fn (): array => ['new' => 'Новый', 'done' => 'Готово'])
            ->displayUsing(static fn (mixed $value): string => strtoupper((string)$value));

        self::assertSame(['new' => 'Новый', 'done' => 'Готово'], $field->getOptions());
        self::assertSame('DONE', $field->renderIndex('done'));
    }

    public function testMultipleSelectDoesNotImplodeValues(): void
    {
        $field = Select::make('Tags', 'TAGS')->multiple()->options(['a' => 'A', 'b' => 'B']);

        self::assertSame(['a', 'b'], $field->normalize(['a', 'b']));
        self::assertSame('A, B', $field->renderDetail(['a', 'b']));
    }

    public function testRequiredValidationHandlesEmptyArrays(): void
    {
        self::assertNotEmpty(Select::make('Tags', 'TAGS')->multiple()->required()->runValidation([]));
    }

    public function testVisibleRequiredAndReadonlyWhenUseConditions(): void
    {
        $field = Text::make('Name', 'NAME')
            ->visibleWhen('TYPE', '=', 'public')
            ->requiredWhen('TYPE', '=', 'public')
            ->readonlyWhen('LOCKED', '=', 'Y');

        self::assertSame(['column' => 'TYPE', 'operator' => '=', 'value' => 'public'], $field->getVisibleWhen());
        self::assertNotEmpty($field->runValidation('', ['TYPE' => 'public']));
        self::assertTrue($field->isReadOnlyFor(['LOCKED' => 'Y']));
    }

    public function testEntitySelectorNormalizesSingleAndMultipleValues(): void
    {
        self::assertSame('7', EntitySelect::make('User', 'USER_ID')->normalize(['7']));
        self::assertSame(['7', '9'], EntitySelect::make('Users', 'USER_IDS')->multiple()->normalize(['7', '9']));
    }

    public function testUserSelectorUsesDialogSelectorAdapter(): void
    {
        $html = UserSelect::make('Responsible', 'RESPONSIBLE_ID')->renderForm(7);

        self::assertStringContainsString('MB.UI.DialogSelector.DialogSelector', $html);
        self::assertStringContainsString("name: 'RESPONSIBLE_ID'", $html);
    }
}
