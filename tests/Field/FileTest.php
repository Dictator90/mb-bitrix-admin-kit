<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\File;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    public function testFluentMethodsAndProperties(): void
    {
        $field = File::make('Test File', 'test_file')
            ->canUpload(false)
            ->canEdit(false)
            ->canDelete(false)
            ->useCloud(false)
            ->medialib(true)
            ->fileDialog(false)
            ->maxCount(5)
            ->uploadType('T')
            ->description(true)
            ->allowedExtensions(['jpg', 'png'])
            ->maxFileSize(1024 * 1024);

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
        $allowedExtensions = $reflection->getProperty('allowedExtensions')->getValue($field);
        $maxFileSize = $reflection->getProperty('maxFileSize')->getValue($field);

        self::assertFalse($canUpload);
        self::assertFalse($canEdit);
        self::assertFalse($canDelete);
        self::assertFalse($useCloud);
        self::assertTrue($medialib);
        self::assertFalse($fileDialog);
        self::assertSame(5, $maxCount);
        self::assertSame('T', $uploadType);
        self::assertTrue($description);
        self::assertSame(['jpg', 'png'], $allowedExtensions);
        self::assertSame(1024 * 1024, $maxFileSize);
    }

    public function testNormalizeIdempotency(): void
    {
        $field = File::make('Test File', 'test_file');

        self::assertNull($field->normalize(null));
        self::assertNull($field->normalize(''));
        self::assertSame(123, $field->normalize(123));
        self::assertSame(123, $field->normalize('123'));

        $multipleField = File::make('Test Multiple', 'test_multiple')->multiple();
        self::assertSame([], $multipleField->normalize(null));
        self::assertSame([123, 456], $multipleField->normalize([123, 456]));
    }

    public function testNormalizeWithDelete(): void
    {
        $context = \Bitrix\Main\Context::getCurrent();
        $originalRequest = $context->getRequest();

        $server = $context->getServer();
        $request = new \Bitrix\Main\HttpRequest(
            $server,
            [],
            ['test_file_del' => ['isset_123' => 'Y']],
            [],
            []
        );
        $context->initialize($request, $context->getResponse(), $server);

        try {
            $field = File::make('Test File', 'test_file');
            $result = $field->normalize(['isset_123' => 123]);
            self::assertNull($result);
        } finally {
            $context->initialize($originalRequest, $context->getResponse(), $server);
        }
    }

    public function testNormalizeMultipleWithDelete(): void
    {
        $context = \Bitrix\Main\Context::getCurrent();
        $originalRequest = $context->getRequest();

        $server = $context->getServer();
        $request = new \Bitrix\Main\HttpRequest(
            $server,
            [],
            ['test_multiple_del' => ['isset_123' => 'Y']],
            [],
            []
        );
        $context->initialize($request, $context->getResponse(), $server);

        try {
            $field = File::make('Test Multiple', 'test_multiple')->multiple();
            $result = $field->normalize(['isset_123' => 123, 'isset_456' => 456]);
            self::assertSame([456], $result);
        } finally {
            $context->initialize($originalRequest, $context->getResponse(), $server);
        }
    }

    public function testNormalizeWithUploadedFilesAndPaths(): void
    {
        // 1. Test $_FILES parsing fallback
        $_FILES['test_file'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => __DIR__ . '/FileTest.php',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ];

        try {
            $field = File::make('Test File', 'test_file');
            $field->normalize([]);
            self::fail('Expected SqlQueryException due to missing b_file table');
        } catch (\Bitrix\Main\DB\SqlQueryException $e) {
            self::assertStringContainsString('b_file', $e->getMessage());
        } finally {
            unset($_FILES['test_file']);
        }

        // 2. Test temporary string path conversion via MakeFileArray
        try {
            $field = File::make('Test File', 'test_file');
            $field->normalize(__DIR__ . '/FileTest.php');
            self::fail('Expected SqlQueryException due to missing b_file table');
        } catch (\Bitrix\Main\DB\SqlQueryException $e) {
            self::assertStringContainsString('b_file', $e->getMessage());
        }
    }

    public function testParseMultipleFileValueFormats(): void
    {
        $field = File::make('Test Multiple', 'test_multiple')->multiple();

        // 1. Test normalize with JSON array string
        self::assertSame([957, 958], $field->normalize('[957, 958]'));
        self::assertSame([957, 958], $field->normalize('["957", "958"]'));

        // 2. Test normalize with serialized PHP array
        $serialized = serialize([957, 958]);
        self::assertSame([957, 958], $field->normalize($serialized));

        // 3. Test normalize with comma-separated list of IDs
        self::assertSame([957, 958], $field->normalize('957, 958'));
        self::assertSame([957, 958], $field->normalize('957,958'));

        // 4. Test prepareFileValue with formats (using reflection to access protected helper)
        $ref = new \ReflectionClass($field);
        $method = $ref->getMethod('parseFileValue');
        $method->setAccessible(true);

        self::assertSame([957, 958], $method->invoke($field, '[957, 958]'));
        self::assertSame([957, 958], $method->invoke($field, $serialized));
        self::assertSame(['957', '958'], $method->invoke($field, '957, 958'));

        // 5. Test OptionSerializer integrations
        self::assertSame($serialized, $field->serializeOptionValue([957, 958]));
        self::assertSame([957, 958], $field->unserializeOptionValue($serialized));
        self::assertSame([957, 958], $field->unserializeOptionValue('[957, 958]'));

        // 6. Test resolveValue when value is an array of IDs itself
        self::assertSame([957, 958], $field->resolveValue([957, 958]));
        self::assertSame([957, 958], $field->resolveValue(['test_multiple' => [957, 958]]));
    }
}
