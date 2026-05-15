<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use LogicException;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ResourcePageResolverStrictTest extends TestCase
{
    public function testInvalidPageEntryThrowsException(): void
    {
        $resource = new class () extends ProductResource {
            public function pages(): iterable
            {
                return [123];
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('class name strings');

        (new ResourcePageResolver())->resolve($resource, 'index');
    }

    public function testDuplicatePageNameThrowsException(): void
    {
        $resource = new class () extends ProductResource {
            public function pages(): iterable
            {
                return [IndexPage::class, DuplicateIndexPage::class];
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Duplicate page name');

        (new ResourcePageResolver())->resolve($resource, 'index');
    }

    public function testMissingPageClassThrowsRuntimeException(): void
    {
        $resource = new class () extends ProductResource {
            public function pages(): iterable
            {
                return ['MB\\Bitrix\\AdminKit\\Page\\MissingCustomPage'];
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        (new ResourcePageResolver())->resolve($resource, 'index');
    }

    public function testValidCustomPagesStillResolve(): void
    {
        $resource = new class () extends ProductResource {
            public function pages(): iterable
            {
                return [CustomStrictIndexPage::class, FormPage::class, DetailPage::class];
            }
        };

        self::assertInstanceOf(
            CustomStrictIndexPage::class,
            (new ResourcePageResolver())->resolve($resource, 'index'),
        );
    }
}

final class DuplicateIndexPage extends IndexPage
{
}

final class CustomStrictIndexPage extends IndexPage
{
}
