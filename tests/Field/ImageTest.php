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
        self::assertFalse($medialib);
        self::assertTrue($fileDialog);
        self::assertNull($maxCount);
        self::assertSame(\Bitrix\Main\UI\FileInput::UPLOAD_IMAGES, $uploadType);
        self::assertFalse($description);
    }

    public function testRenderFormField(): void
    {
        // bitrix-core-test does not ship the main.core extension assets. The
        // FileInput markup itself only needs the core to be considered loaded.
        \CJSCore::markExtensionLoaded('core');

        $field = Image::make('Test Image', 'test_image')
            ->useCloud(false)
            ->fileDialog(false);
        $html = $field->renderFormField();

        self::assertStringContainsString('adm-fileinput-wrapper-single', $html);
        self::assertStringContainsString('"name":"test_image[#IND#]"', $html);
        self::assertStringContainsString('new BX.UI.FileInput', $html);
        self::assertStringContainsString('"allowUpload":"I"', $html);
    }
}
