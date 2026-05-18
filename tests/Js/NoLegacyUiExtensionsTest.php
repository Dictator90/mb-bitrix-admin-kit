<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Js;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class NoLegacyUiExtensionsTest extends TestCase
{
    public function testSrcDoesNotReferenceLegacyUiExtensions(): void
    {
        $srcRoot = dirname(__DIR__, 2) . '/src';
        $forbidden = ['mb.ui.tabs', 'mb.ui.dialog-selector'];

        foreach ($forbidden as $needle) {
            self::assertSame(
                [],
                $this->grep($srcRoot, $needle),
                'Found forbidden reference: ' . $needle,
            );
        }
    }

    /**
     * @return list<string>
     */
    private function grep(string $directory, string $needle): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $files = new RegexIterator($iterator, '/\.php$/');

        $hits = [];
        foreach ($files as $file) {
            $contents = (string)file_get_contents($file->getPathname());
            if (str_contains($contents, $needle)) {
                $hits[] = str_replace(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        return $hits;
    }
}
