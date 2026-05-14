<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Manager;

use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Manager\SidePanelAdapter;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Support\UrlGenerator;
use PHPUnit\Framework\TestCase;

final class SidePanelToolbarAssetV080Test extends TestCase
{
    public function testSidePanelAdapterUsesResourceSettings(): void
    {
        $resource = new SidePanelResource();
        $adapter = new SidePanelAdapter($resource, new UrlGenerator('/admin/test.php?page=side'));

        self::assertTrue($adapter->shouldOpen('add'));
        self::assertTrue($adapter->shouldOpen('edit'));
        self::assertFalse($adapter->shouldOpen('detail'));
        self::assertStringContainsString('IFRAME=Y', $adapter->openJs('/admin/test.php?page=side&action=add'));
        self::assertStringContainsString('width: 900', $adapter->openJs('/admin/test.php?page=side&action=add', $resource->getGridId()));
        self::assertStringContainsString('adminkit_sidepanel_side_edit_7', $adapter->sidePanelId('edit', 7));
    }

    public function testToolbarActionVisibilityUsesAdminCondition(): void
    {
        $action = ToolbarAction::make('Export')->url('/export')->canSee(fn (array $context): bool => $context['allowed'] === true);

        self::assertTrue($action->isVisible(['allowed' => true]));
        self::assertFalse($action->isVisible(['allowed' => false]));
        self::assertStringContainsString('/export', $action->render());
    }

    public function testAssetManagerDeduplicatesAssets(): void
    {
        $assets = (new AssetManager())->forGrid()->forGrid()->forSidePanel()->addCss('/a.css')->addCss('/a.css')->all();

        self::assertSame(['main.ui.grid', 'main.ui.filter', 'ui.buttons', 'ui.toolbar', 'sidepanel'], $assets['extensions']);
        self::assertSame(['/a.css'], $assets['css']);
    }
}

final class SidePanelResource extends BaseTestResource
{
    protected string $title = 'Side';
    public static function getId(): string
    {
        return 'side';
    }
    public function useSidePanel(): bool
    {
        return true;
    }
    public function detailInSidePanel(): bool
    {
        return false;
    }
    public function sidePanelWidth(): int
    {
        return 900;
    }
}
