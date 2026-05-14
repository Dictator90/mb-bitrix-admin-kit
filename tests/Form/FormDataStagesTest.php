<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Form;

use MB\Bitrix\AdminKit\Form\FormData;
use PHPUnit\Framework\TestCase;

final class FormDataStagesTest extends TestCase
{
    public function testStoresRawNormalizedValidatedAndErrors(): void
    {
        $data = (new FormData())
            ->withRaw(['NAME' => ' raw '])
            ->withNormalized(['NAME' => 'raw'])
            ->withValidated(['NAME' => 'raw']);
        $data->addError('NAME', 'Invalid');

        self::assertSame(['NAME' => ' raw '], $data->raw());
        self::assertSame(['NAME' => 'raw'], $data->normalized());
        self::assertSame(['NAME' => 'raw'], $data->validated());
        self::assertSame(['NAME' => ['Invalid']], $data->errors());
    }
}
