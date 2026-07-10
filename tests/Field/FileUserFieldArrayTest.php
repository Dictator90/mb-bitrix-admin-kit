<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\File;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Covers the UserField "file" column mode ({@see File::ormExpectsFileArray()}):
 * the field hands the ORM a fresh file array instead of a pre-saved id, so the
 * value must pass through the POST-serialization step untouched and normalize
 * without ever calling {@see \CFile::SaveFile()} (i.e. without touching b_file).
 */
final class FileUserFieldArrayTest extends TestCase
{
    public function testOrmExpectsFileArrayIsFluentAndSetsFlag(): void
    {
        $field = File::make('Doc', 'UF_DOC');

        self::assertSame($field, $field->ormExpectsFileArray());

        $flag = (new ReflectionClass($field))->getProperty('ormExpectsFileArray')->getValue($field);
        self::assertTrue($flag);
    }

    public function testSerializePostValuePassesThroughInFileArrayMode(): void
    {
        $field = File::make('Doc', 'UF_DOC')->ormExpectsFileArray();

        $fileArray = [
            'name' => 'doc.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/whatever',
            'error' => UPLOAD_ERR_OK,
            'size' => 5,
        ];

        self::assertSame($fileArray, $field->serializePostValue($fileArray));
        self::assertSame('123', $field->serializePostValue('123'));
    }

    public function testNormalizeReturnsFileArrayWithoutSavingToDatabase(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'akuf');
        self::assertIsString($tmp);
        file_put_contents($tmp, 'hello');

        $_FILES['UF_DOC'] = [
            'name' => 'doc.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp),
        ];

        try {
            $field = File::make('Doc', 'UF_DOC')->ormExpectsFileArray();

            $result = $field->normalize([]);

            self::assertIsArray($result);
            self::assertSame('doc.txt', $result['name'] ?? null);
            self::assertSame($tmp, $result['tmp_name'] ?? null);
        } finally {
            unset($_FILES['UF_DOC']);
            @unlink($tmp);
        }
    }
}
