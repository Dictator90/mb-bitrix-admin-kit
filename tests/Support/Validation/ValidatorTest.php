<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support\Validation;

use InvalidArgumentException;
use MB\Bitrix\AdminKit\Support\Validation\Rules;
use MB\Bitrix\AdminKit\Support\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidateReturnsTrueWhenAllRulesPass(): void
    {
        $validator = new Validator();
        $passed = $validator->validate(
            ['EMAIL' => 'user@example.com'],
            ['EMAIL' => [Rules::email()]],
        );

        self::assertTrue($passed);
        self::assertSame([], $validator->getErrors());
    }

    public function testValidateCollectsFieldErrors(): void
    {
        $validator = new Validator();
        $passed = $validator->validate(
            ['EMAIL' => 'invalid'],
            ['EMAIL' => [Rules::email()]],
        );

        self::assertFalse($passed);
        self::assertArrayHasKey('EMAIL', $validator->getErrors());
    }

    public function testValidateOrFailThrowsOnErrors(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Validator())->validateOrFail(
            ['EMAIL' => 'invalid'],
            ['EMAIL' => [Rules::email()]],
        );
    }
}
