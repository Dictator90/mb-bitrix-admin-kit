<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

/**
 * Настройки грида (флаги и режимы), которые маппятся на параметры bitrix:main.ui.grid
 * в {@see \MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridAdapter}.
 *
 * Дефолты совпадают с прежним «зашитым» поведением адаптера — обратная совместимость.
 * Заполняется из ресурса через {@see self::fromResource()} (хуки трейта HasResourceGrid).
 */
final class GridSettings
{
    /**
     * @param int[] $pageSizes
     * @param array<int|string,mixed> $aggregates
     * @param array<int|string,mixed> $footer
     */
    public function __construct(
        public bool $allowColumnsSort = true,
        public bool $allowColumnsResize = true,
        public bool $allowHorizontalScroll = true,
        public bool $allowRowsSort = false,
        public bool $allowContextMenu = false,
        public bool $pinHeader = false,
        public bool $stickedColumns = false,
        public bool $showGridSettingsMenu = true,
        public bool $enableFieldsSearch = false,
        public bool $showSelectedCounter = true,
        public bool $showTotalCounter = true,
        public bool $showPageSizeSelector = false,
        public bool $useAjax = true,
        public array $pageSizes = [],
        public ?string $emptyMessage = null,
        public array $aggregates = [],
        public array $footer = [],
        public bool $tileMode = false,
        public ?string $tileSize = null,
        public ?string $tileItemJsClass = null,
        public ?string $rowLayout = null,
    ) {
    }

    /**
     * Считывает настройки из ресурса. Каждый хук опционален (method_exists),
     * отсутствие хука — дефолтное значение.
     */
    public static function fromResource(object $resource): self
    {
        $settings = new self();

        $bool = static function (string $method, bool $default) use ($resource): bool {
            return method_exists($resource, $method) ? (bool)$resource->{$method}() : $default;
        };

        $settings->allowColumnsSort = $bool('allowColumnsSort', $settings->allowColumnsSort);
        $settings->allowColumnsResize = $bool('allowColumnsResize', $settings->allowColumnsResize);
        $settings->allowHorizontalScroll = $bool('allowHorizontalScroll', $settings->allowHorizontalScroll);
        $settings->allowRowsSort = $bool('allowRowsSort', $settings->allowRowsSort);
        $settings->allowContextMenu = $bool('allowContextMenu', $settings->allowContextMenu);
        $settings->pinHeader = $bool('pinHeader', $settings->pinHeader);
        $settings->stickedColumns = $bool('stickedColumns', $settings->stickedColumns);
        $settings->showGridSettingsMenu = $bool('showGridSettingsMenu', $settings->showGridSettingsMenu);
        $settings->enableFieldsSearch = $bool('enableFieldsSearch', $settings->enableFieldsSearch);
        $settings->showSelectedCounter = $bool('showSelectedCounter', $settings->showSelectedCounter);
        $settings->showTotalCounter = $bool('showTotalCounter', $settings->showTotalCounter);
        $settings->useAjax = $bool('useAjax', $settings->useAjax);
        $settings->tileMode = $bool('tileMode', $settings->tileMode);

        if (method_exists($resource, 'pageSizes')) {
            $sizes = $resource->pageSizes();
            if (is_array($sizes)) {
                $settings->pageSizes = array_values(array_filter(array_map('intval', $sizes), static fn (int $n): bool => $n > 0));
            }
        }
        $settings->showPageSizeSelector = $settings->pageSizes !== [];

        if (method_exists($resource, 'gridEmptyMessage')) {
            $message = $resource->gridEmptyMessage();
            $settings->emptyMessage = ($message === null || $message === '') ? null : (string)$message;
        }

        if (method_exists($resource, 'gridAggregates')) {
            $aggregates = $resource->gridAggregates();
            $settings->aggregates = is_array($aggregates) ? $aggregates : [];
        }

        if (method_exists($resource, 'gridFooter')) {
            $footer = $resource->gridFooter();
            $settings->footer = is_array($footer) ? $footer : [];
        }

        if (method_exists($resource, 'tileSize')) {
            $size = $resource->tileSize();
            $settings->tileSize = ($size === null || $size === '') ? null : (string)$size;
        }
        if (method_exists($resource, 'tileItemJsClass')) {
            $jsClass = $resource->tileItemJsClass();
            $settings->tileItemJsClass = ($jsClass === null || $jsClass === '') ? null : (string)$jsClass;
        }
        if (method_exists($resource, 'rowLayout')) {
            $layout = $resource->rowLayout();
            $settings->rowLayout = ($layout === null || $layout === '') ? null : (string)$layout;
        }

        return $settings;
    }
}
