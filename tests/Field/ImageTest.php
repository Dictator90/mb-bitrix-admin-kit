<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Image;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    public function testFluentMethodsAndDefaultProperties(): void
    {
        $field = Image::make('Test Image', 'test_image');

        $reflection = new \ReflectionClass($field);

        $canUpload = $reflection->getProperty('canUpload')->getValue($field);
        $canEdit = $reflection->getProperty('canEdit')->getValue($field);
        $canDelete = $reflection->getProperty('canDelete')->getValue($field);
        $useCloud = $reflection->getProperty('useCloud')->getValue($field);
        $medialib = $reflection->getProperty('medialib')->getValue($field);
        $fileDialog = $reflection->getProperty('fileDialog')->getValue($field);
        $maxCount = $reflection->getProperty('maxCount')->getValue($field);
        $uploadType = $reflection->getProperty('uploadType')->getValue($field);
        $description = $reflection->getProperty('description')->getValue($field);

        self::assertTrue($canUpload);
        self::assertTrue($canEdit);
        self::assertTrue($canDelete);
        self::assertTrue($useCloud);
        self::assertTrue($medialib);
        self::assertTrue($fileDialog);
        self::assertNull($maxCount);
        self::assertSame(\Bitrix\Main\UI\FileInput::UPLOAD_IMAGES, $uploadType);
        self::assertTrue($description);
    }

    public function testRenderFormField(): void
    {
        $field = Image::make('Test Image', 'test_image');
        $html = $field->renderFormField();

        self::assertStringContainsString('bx_file_test_image__ind_', $html);
        self::assertStringContainsString('bx_file_test_image__ind__input_container', $html);
        self::assertStringContainsString('BX.UI.ImageInput.getById', $html);
    }
}
