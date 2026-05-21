<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Form;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use PHPUnit\Framework\TestCase;

final class DataPipelineReadonlyTest extends TestCase
{
    public function testReadonlyOnUpdateSkipsValidationButKeepsNormalizedValue(): void
    {
        $field = Text::make('Code', 'CODE')->required()->readonlyOnUpdate();
        $formData = (new DataPipeline())->process([$field], [
            'CODE' => '',
            '_mode' => 'edit',
            '_id' => '10',
        ]);

        self::assertFalse($formData->hasErrors());
        self::assertSame('', $formData->normalized()['CODE']);
        self::assertArrayNotHasKey('CODE', $formData->validated());
    }
}
