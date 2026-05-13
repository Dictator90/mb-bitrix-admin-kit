<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Form;

use MB\Bitrix\AdminKit\Form\FormData;
use PHPUnit\Framework\TestCase;

final class FormDataTest extends TestCase
{
    public function testItStoresDataAndErrors(): void
    {
        $data = (new FormData(['NAME' => ' Raw ']))->withNormalized(['NAME' => 'Raw'])->withValidated(['NAME' => 'Raw']);
        $data->addError('NAME', 'Error');
        self::assertSame('Raw', $data->get('NAME'));
        self::assertTrue($data->hasErrors());
    }
}
