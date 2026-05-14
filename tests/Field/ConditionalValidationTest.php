<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use PHPUnit\Framework\TestCase;

final class ConditionalValidationTest extends TestCase
{
    public function testRequiredWhenShortForm(): void
    {
        $field = Text::make('Email', 'EMAIL')->requiredWhen('SUBSCRIBE', '=', 'Y');

        self::assertNotEmpty($field->runValidation('', ['SUBSCRIBE' => 'Y']));
        self::assertSame([], $field->runValidation('', ['SUBSCRIBE' => 'N']));
    }

    public function testRequiredWhenConditionTree(): void
    {
        $field = Text::make('URL', 'EXTERNAL_URL')->requiredWhen(
            AdminCondition::make()->where('TYPE', '=', 'external')
        );

        self::assertNotEmpty($field->runValidation('', ['TYPE' => 'external']));
    }
}
