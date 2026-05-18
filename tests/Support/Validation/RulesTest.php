<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support\Validation;

use MB\Bitrix\AdminKit\Support\Validation\Rules;
use PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase
{
    public function testMinLengthRulePassesForEmptyValue(): void
    {
        $rule = Rules::minLength(3);
        self::assertTrue($rule(''));
    }

    public function testMinLengthRuleFailsWhenTooShort(): void
    {
        $rule = Rules::minLength(3);
        $result = $rule('ab');
        self::assertIsString($result);
        self::assertStringContainsString('3', (string) $result);
    }

    public function testEmailRuleRejectsInvalidAddress(): void
    {
        $rule = Rules::email();
        self::assertIsString($rule('not-an-email'));
    }

    public function testEmailRuleAcceptsValidAddress(): void
    {
        $rule = Rules::email();
        self::assertTrue($rule('user@example.com'));
    }

    public function testInRuleAcceptsAllowedValues(): void
    {
        $rule = Rules::in(['a', 'b']);
        self::assertTrue($rule('a'));
        self::assertIsString($rule('c'));
    }
}
