<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Resource\Resource;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductTable;
use PHPUnit\Framework\TestCase;

final class ResourceBackwardsCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        ProductTable::reset();
    }

    public function testLegacyResourceExtendingResourceKeepsPagesAndGridIdentity(): void
    {
        $resource = new LegacyDirectResource();

        self::assertSame('legacy-direct', LegacyDirectResource::getId());
        self::assertInstanceOf(IndexPage::class, $resource->indexPage());
        self::assertInstanceOf(FormPage::class, $resource->formPage());
        self::assertInstanceOf(DetailPage::class, $resource->detailPage(1));
        self::assertNotEmpty($resource->getGridId());
        self::assertNotEmpty($resource->getFilterId());
        self::assertSame('vendor_product', $resource->databaseTableName());
    }

    public function testLegacyResourceKeepsCrudHelpersWhenDataManagerIsConfigured(): void
    {
        $resource = new LegacyDirectResource();

        self::assertTrue($resource->hasCrud());
        self::assertSame(ProductTable::class, $resource->getDataManagerClass());
        self::assertSame(['ID' => 1, 'NAME' => 'One'], $resource->findItem(1));
    }
}

final class LegacyDirectResource extends Resource
{
    use \MB\Bitrix\AdminKit\Resource\Concerns\HasDataManager;
    use \MB\Bitrix\AdminKit\Resource\Concerns\HasDataManagerPersistence;

    protected ?string $dataManagerClass = ProductTable::class;

    public function indexFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')];
    }

    public static function getId(): string
    {
        return 'legacy-direct';
    }
}
