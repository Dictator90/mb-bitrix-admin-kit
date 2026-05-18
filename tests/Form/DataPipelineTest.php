<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Form;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Support\Validation\Rules;
use PHPUnit\Framework\TestCase;

final class DataPipelineTest extends TestCase
{
    public function testItNormalizesAndValidatesFields(): void
    {
        $name = Text::make('Name', 'NAME')->required();
        $email = Text::make('Email', 'EMAIL')->validate(Rules::email());

        $formData = (new DataPipeline())->process(
            [$name, $email],
            ['NAME' => '  Alice  ', 'EMAIL' => 'alice@example.com'],
        );

        self::assertSame('  Alice  ', $formData->raw()['NAME']);
        self::assertSame('  Alice  ', $formData->normalized()['NAME']);
        self::assertFalse($formData->hasErrors());
    }

    public function testItCollectsValidationErrors(): void
    {
        $name = Text::make('Name', 'NAME')->required();

        $formData = (new DataPipeline())->process([$name], ['NAME' => '']);

        self::assertTrue($formData->hasErrors());
        self::assertArrayHasKey('NAME', $formData->errors());
    }

    public function testItSkipsReadonlyFieldsDuringValidation(): void
    {
        $name = Text::make('Name', 'NAME')->required()->readonly();

        $formData = (new DataPipeline())->process([$name], ['NAME' => '']);

        self::assertFalse($formData->hasErrors());
    }
}
