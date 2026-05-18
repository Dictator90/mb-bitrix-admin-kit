<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Compatibility;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\AdminKit;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Database\DbResult;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\Support\UrlGenerator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class PublicApiCompatibilityTest extends TestCase
{
    public function testStablePublicClassesAreLoadable(): void
    {
        self::assertTrue(class_exists(AdminKit::class));
        self::assertTrue(class_exists(AdminKitManager::class));
        self::assertTrue(class_exists(Resource::class));
        self::assertTrue(class_exists(CrudResource::class));
        self::assertTrue(class_exists(GridContext::class));
        self::assertTrue(class_exists(FormData::class));
        self::assertTrue(class_exists(DbResult::class));
        self::assertTrue(class_exists(BulkResult::class));
        self::assertTrue(class_exists(Text::class));
        self::assertTrue(class_exists(TextFilter::class));
        self::assertTrue(class_exists(BulkAction::class));
        self::assertTrue(class_exists(AdminCollection::class));
        self::assertTrue(class_exists(AdminString::class));
        self::assertTrue(class_exists(AdminCondition::class));
        self::assertTrue(class_exists(UrlGenerator::class));
    }

    public function testSourceDoesNotDeclareGlobalSupportHelpersOrCallThemDirectly(): void
    {
        foreach ($this->sourceFiles() as $file) {
            $contents = (string)file_get_contents($file->getPathname());

            self::assertSame([], $this->globalHelperCalls($contents), $file->getPathname());
        }
    }

    /** @return iterable<SplFileInfo> */
    private function sourceFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src'));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    /** @return string[] */
    private function globalHelperCalls(string $contents): array
    {
        $tokens = token_get_all($contents);
        $helpers = ['collect' => true, 'str' => true, 'condition' => true];
        $calls = [];

        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_STRING || !isset($helpers[strtolower($token[1])])) {
                continue;
            }

            $previous = $this->previousMeaningfulToken($tokens, $index);
            $next = $this->nextMeaningfulToken($tokens, $index);

            if ($next !== '(' || $this->isNonGlobalCallPreviousToken($previous)) {
                continue;
            }

            $calls[] = $token[1];
        }

        return $calls;
    }

    /** @param array<int, mixed> $tokens */
    private function previousMeaningfulToken(array $tokens, int $index): mixed
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    /** @param array<int, mixed> $tokens */
    private function nextMeaningfulToken(array $tokens, int $index): mixed
    {
        $count = count($tokens);
        for ($i = $index + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    private function isNonGlobalCallPreviousToken(mixed $token): bool
    {
        if (is_array($token)) {
            return in_array($token[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR], true);
        }

        return $token === '\\';
    }
}
