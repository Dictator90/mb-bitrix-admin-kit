<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Crud\DetailPage;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Page\PageFactory;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class ResourcePagesTest extends TestCase
{
    public function testDefaultResourcePagesAreRegistered(): void
    {
        $pages = iterator_to_array((new ProductResource())->pages());

        self::assertSame([IndexPage::class, FormPage::class, DetailPage::class], $pages);
    }

    public function testPageFactoryPassesResourceIdAndParams(): void
    {
        $resource = new ProductResource();
        $page = (new PageFactory())->make(TestFormPage::class, $resource, 7, ['mode' => 'edit']);

        self::assertInstanceOf(TestFormPage::class, $page);
        self::assertSame($resource, $page->resource());
        self::assertSame('form', $page::pageName());
        self::assertSame('edit', $page->modeValue());
    }

    public function testResolverUsesCustomPageClassesFromResourcePages(): void
    {
        $resource = new class () extends ProductResource {
            public function pages(): iterable
            {
                return [CustomIndexPage::class, CustomFormPage::class, CustomDetailPage::class];
            }
        };

        self::assertInstanceOf(CustomIndexPage::class, (new ResourcePageResolver())->resolve($resource, 'index'));
        self::assertInstanceOf(CustomFormPage::class, (new ResourcePageResolver())->resolve($resource, 'form'));
        self::assertInstanceOf(CustomDetailPage::class, (new ResourcePageResolver())->resolve($resource, 'detail', 1));
    }

    public function testCustomIndexPageFieldsOverrideResourceShortcut(): void
    {
        $resource = new ProductResource();
        $page = new CustomIndexPage($resource);

        self::assertSame(['CUSTOM_NAME'], array_map(
            static fn ($field): string => $field->getColumn(),
            iterator_to_array($page->definition()->fields()),
        ));
    }
}

final class CustomIndexPage extends IndexPage
{
    public function fields(): iterable
    {
        return [Text::make('Custom name', 'CUSTOM_NAME')];
    }
}

final class CustomFormPage extends FormPage
{
}

final class CustomDetailPage extends DetailPage
{
}

final class TestFormPage extends FormPage
{
    public function modeValue(): string
    {
        return $this->mode;
    }
}
