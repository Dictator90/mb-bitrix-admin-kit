<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Password;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function testOldValueShowsStoredValueAndToggleByDefault(): void
    {
        $html = Password::make('Secret', 'secret')->renderFormField('my-secret');

        self::assertStringContainsString('value="my-secret"', $html);
        self::assertStringContainsString('adminkit-password-field', $html);
        self::assertStringContainsString('data-adminkit-password-toggle', $html);
        self::assertStringContainsString('ui-ctl-icon-crossed-eye', $html);
        self::assertStringNotContainsString('Leave empty to keep current value', $html);
    }

    public function testOldValueDisabledKeepsEmptyInputAndHint(): void
    {
        $html = Password::make('Secret', 'secret')->oldValue(false)->renderFormField('my-secret');

        self::assertStringContainsString('value=""', $html);
        self::assertStringNotContainsString('adminkit-password-field', $html);
        self::assertStringNotContainsString('data-adminkit-password-toggle', $html);
        self::assertStringContainsString('Stored value is preserved', $html);
    }

    public function testPreserveStoredValueWhenEmptyIsAlwaysEnabled(): void
    {
        self::assertTrue(Password::make('Secret', 'secret')->preserveStoredValueWhenEmpty());
        self::assertTrue(Password::make('Secret', 'secret')->oldValue(false)->preserveStoredValueWhenEmpty());
    }
}
