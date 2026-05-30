<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\GridSettings;
use PHPUnit\Framework\TestCase;

final class GridSettingsTest extends TestCase
{
    public function testDefaults(): void
    {
        $settings = new GridSettings();

        self::assertTrue($settings->allowColumnsSort);
        self::assertTrue($settings->allowColumnsResize);
        self::assertTrue($settings->allowHorizontalScroll);
        self::assertFalse($settings->allowRowsSort);
        self::assertFalse($settings->allowContextMenu);
        self::assertFalse($settings->pinHeader);
        self::assertTrue($settings->useAjax);
        self::assertSame([], $settings->pageSizes);
        self::assertFalse($settings->showPageSizeSelector);
        self::assertNull($settings->emptyMessage);
        self::assertFalse($settings->tileMode);
    }

    public function testFromResourceReadsHooks(): void
    {
        $resource = new class () {
            public function allowColumnsSort(): bool
            {
                return false;
            }

            public function allowRowsSort(): bool
            {
                return true;
            }

            public function pinHeader(): bool
            {
                return true;
            }

            public function useAjax(): bool
            {
                return false;
            }

            /** @return int[] */
            public function pageSizes(): array
            {
                return [5, 10, 0, -3, 25];
            }

            public function gridEmptyMessage(): ?string
            {
                return 'Пусто';
            }

            public function tileMode(): bool
            {
                return true;
            }

            public function tileSize(): ?string
            {
                return 'l';
            }
        };

        $settings = GridSettings::fromResource($resource);

        self::assertFalse($settings->allowColumnsSort);
        self::assertTrue($settings->allowRowsSort);
        self::assertTrue($settings->pinHeader);
        self::assertFalse($settings->useAjax);
        // невалидные размеры (0, -3) отфильтрованы
        self::assertSame([5, 10, 25], $settings->pageSizes);
        self::assertTrue($settings->showPageSizeSelector);
        self::assertSame('Пусто', $settings->emptyMessage);
        self::assertTrue($settings->tileMode);
        self::assertSame('l', $settings->tileSize);
    }

    public function testFromResourceWithoutHooksUsesDefaults(): void
    {
        $resource = new class () {
        };

        $settings = GridSettings::fromResource($resource);

        self::assertTrue($settings->allowColumnsSort);
        self::assertFalse($settings->allowRowsSort);
        self::assertSame([], $settings->pageSizes);
        self::assertNull($settings->emptyMessage);
    }
}
