<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Phone;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PhoneTest extends TestCase
{
    public function testNormalizeCanonicalisesToPlusAndDigits(): void
    {
        $field = Phone::make('Phone', 'PHONE');

        self::assertSame('+79999999999', $field->normalize('+7 (999) 999-99-99'));
        self::assertSame('+79999999999', $field->normalize('+7 999 999 99 99'));
        self::assertSame('89999999999', $field->normalize('8 (999) 999-99-99'));
    }

    public function testNormalizeEmptyOrDigitlessBecomesNull(): void
    {
        $field = Phone::make('Phone', 'PHONE');

        self::assertNull($field->normalize(''));
        self::assertNull($field->normalize('   '));
        self::assertNull($field->normalize('+()-'));
        self::assertNull($field->normalize(null));
    }

    public function testNormalizeArrayTakesFirstValueInSingleMode(): void
    {
        $field = Phone::make('Phone', 'PHONE');

        self::assertSame('+71112223344', $field->normalize(['+7 (111) 222-33-44', '+7 (999) 000-00-00']));
    }

    public function testMultipleNormalizesEachAndDropsEmpties(): void
    {
        $field = Phone::make('Phones', 'PHONES')->multiple();

        self::assertSame(
            ['+79990001122', '+13334445566'],
            $field->normalize(['+7 (999) 000-11-22', '   ', '+1 (333) 444-5566']),
        );
        self::assertSame([], $field->normalize(null));
        self::assertSame([], $field->normalize([]));
    }

    public function testDefaultMasksAreRuAndUs(): void
    {
        $field = Phone::make('Phone', 'PHONE');

        self::assertSame([Phone::MASK_RU, Phone::MASK_US], $this->normalizedMasks($field));
    }

    public function testSingleMaskAsString(): void
    {
        $field = Phone::make('Phone', 'PHONE')->mask(Phone::MASK_INTL);

        self::assertSame([Phone::MASK_INTL], $this->normalizedMasks($field));
    }

    public function testMaskArrayIsFilteredFromEmpties(): void
    {
        $field = Phone::make('Phone', 'PHONE')->mask([Phone::MASK_RU, '', '  ', Phone::MASK_US]);

        self::assertSame([Phone::MASK_RU, Phone::MASK_US], $this->normalizedMasks($field));
    }

    public function testWithoutMaskDisablesMasks(): void
    {
        self::assertSame([], $this->normalizedMasks(Phone::make('Phone', 'PHONE')->withoutMask()));
        self::assertSame([], $this->normalizedMasks(Phone::make('Phone', 'PHONE')->mask(null)));
    }

    public function testSingleMaskRendersRawAttributeWithMaxlength(): void
    {
        $field = Phone::make('Phone', 'PHONE')->mask(Phone::MASK_RU);

        $attrs = $this->scalarInputExtraAttrs($field);

        self::assertStringContainsString('data-phone-mask="' . htmlspecialcharsbx(Phone::MASK_RU) . '"', $attrs);
        self::assertStringContainsString('maxlength="' . strlen(Phone::MASK_RU) . '"', $attrs);
        self::assertStringContainsString('inputmode="tel"', $attrs);
    }

    public function testMultipleMasksRenderAsJsonWithLongestMaxlength(): void
    {
        $field = Phone::make('Phone', 'PHONE')->mask([Phone::MASK_RU, Phone::MASK_US]);

        $attrs = $this->scalarInputExtraAttrs($field);
        $expectedJson = (string) json_encode([Phone::MASK_RU, Phone::MASK_US], JSON_UNESCAPED_UNICODE);
        $expectedMax = max(strlen(Phone::MASK_RU), strlen(Phone::MASK_US));

        self::assertStringContainsString('data-phone-mask="' . htmlspecialcharsbx($expectedJson) . '"', $attrs);
        self::assertStringContainsString('maxlength="' . $expectedMax . '"', $attrs);
    }

    public function testWithoutMaskOmitsMaskAttribute(): void
    {
        $attrs = $this->scalarInputExtraAttrs(Phone::make('Phone', 'PHONE')->withoutMask());

        self::assertStringNotContainsString('data-phone-mask', $attrs);
        self::assertStringNotContainsString('maxlength', $attrs);
        self::assertStringContainsString('inputmode="tel"', $attrs);
    }

    /**
     * @return array<int, string>
     */
    private function normalizedMasks(Phone $field): array
    {
        $method = new ReflectionMethod($field, 'normalizedMasks');
        $method->setAccessible(true);

        /** @var array<int, string> $result */
        $result = $method->invoke($field);

        return $result;
    }

    private function scalarInputExtraAttrs(Phone $field): string
    {
        $method = new ReflectionMethod($field, 'scalarInputExtraAttrs');
        $method->setAccessible(true);

        return (string) $method->invoke($field);
    }
}
