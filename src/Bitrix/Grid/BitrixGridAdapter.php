<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminString;

final class BitrixGridAdapter
{
    public function __construct(
        private readonly BitrixGridActionPanelAdapter $actionPanelAdapter = new BitrixGridActionPanelAdapter(),
    ) {
    }

    /** @return array<string,mixed> */
    public function componentParams(Grid $grid): array
    {
        $sortParams = (new GridOptions($grid->getId()))->getSorting([
            'sort' => [$grid->getPrimaryKey() => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);

        $inlineEditable = $grid->hasEditableFields();
        $hasActions = $grid->getBulkActions() !== [] || $inlineEditable;
        $settings = $grid->settings();

        $params = [
            'GRID_ID' => $grid->getId(),
            'COLUMNS' => array_map(
                static fn (FieldContract $field): array => $field->getGridColumnConfig(),
                AdminCollection::make($grid->getFields())->all(),
            ),
            'ROWS' => $grid->getRows(),
            'SORT' => $sortParams['sort'],
            'SORT_VARS' => $sortParams['vars'],
            'NAV_OBJECT' => $grid->getPagination(),
            'TOTAL_ROWS_COUNT' => $grid->getTotalCount(),
            'SHOW_NAVIGATION_PANEL' => $grid->shouldShowNavigation(),
            'SHOW_PAGINATION' => $grid->shouldShowNavigation(),
            'SHOW_ROW_CHECKBOXES' => $hasActions,
            'SHOW_CHECK_ALL_CHECKBOXES' => $hasActions,
            'SHOW_SELECT_ALL_RECORDS_CHECKBOX' => $grid->shouldShowSelectAllRecordsCheckbox(),
            'SHOW_ACTION_PANEL' => $hasActions,
            'ACTION_PANEL' => $hasActions ? $this->actionPanelAdapter->componentParams($grid) : null,
            'ALLOW_INLINE_EDIT' => $inlineEditable,
            'ALLOW_EDIT_SELECTION' => $inlineEditable,
            'ALLOW_COLUMNS_SORT' => $settings->allowColumnsSort,
            'ALLOW_COLUMNS_RESIZE' => $settings->allowColumnsResize,
            'ALLOW_HORIZONTAL_SCROLL' => $settings->allowHorizontalScroll,
            'ALLOW_ROWS_SORT' => $settings->allowRowsSort,
            // Порядок сохраняем своим обработчиком (GridRowSort/IndexRowSortHandler),
            // нативный instant-save (в grid user options) отключаем.
            'ALLOW_ROWS_SORT_INSTANT_SAVE' => false,
            'ALLOW_CONTEXT_MENU' => $settings->allowContextMenu,
            'ALLOW_PIN_HEADER' => $settings->pinHeader,
            'ALLOW_STICKED_COLUMNS' => $settings->stickedColumns,
            'SHOW_GRID_SETTINGS_MENU' => $settings->showGridSettingsMenu,
            'ENABLE_FIELDS_SEARCH' => $settings->enableFieldsSearch,
            'SHOW_SELECTED_COUNTER' => $settings->showSelectedCounter,
            'SHOW_TOTAL_COUNTER' => $settings->showTotalCounter,
            'AJAX_MODE' => $settings->useAjax ? 'Y' : 'N',
            'AJAX_OPTION_HISTORY' => 'N',
            'AJAX_OPTION_JUMP' => 'N',
        ];

        if ($settings->pageSizes !== []) {
            $params['SHOW_PAGESIZE'] = true;
            $params['PAGE_SIZES'] = array_map(
                static fn (int $n): array => ['NAME' => (string)$n, 'VALUE' => $n],
                $settings->pageSizes,
            );
        }

        if ($settings->emptyMessage !== null) {
            // Текст-заглушка для пустого грида (main-grid-empty-block).
            $params['STUB'] = $settings->emptyMessage;
        }

        if ($settings->aggregates !== []) {
            $params['AGGREGATE'] = $settings->aggregates;
        }

        if ($settings->footer !== []) {
            $params['FOOTER'] = $settings->footer;
        }

        if ($settings->tileMode || $settings->tileSize !== null || $settings->tileItemJsClass !== null) {
            $params['TILE_GRID_MODE'] = true;
            if ($settings->tileSize !== null) {
                $params['TILE_SIZE'] = $settings->tileSize;
            }
            if ($settings->tileItemJsClass !== null) {
                $params['JS_CLASS_TILE_GRID_ITEM'] = $settings->tileItemJsClass;
            }
        }

        if ($settings->rowLayout !== null) {
            $params['ROW_LAYOUT'] = $settings->rowLayout;
        }

        if ($grid->hasCollapsibleRows()) {
            $params['ENABLE_COLLAPSIBLE_ROWS'] = true;
            $params['COLUMNS'] = $this->applyCollapsibleShiftColumn($params['COLUMNS'], $grid);
            $params['COLUMNS'] = $this->applyGroupingColumnAlign($params['COLUMNS'], $grid);
        }

        if (!$hasActions) {
            unset($params['ACTION_PANEL']);
        }

        return $params;
    }

    /**
     * @param array<int,array<string,mixed>> $columns
     * @return array<int,array<string,mixed>>
     */
    private function applyCollapsibleShiftColumn(array $columns, Grid $grid): array
    {
        $shiftColumnId = $grid->collapsibleShiftColumnId();
        if ($shiftColumnId === null || $shiftColumnId === '') {
            return $columns;
        }

        $shiftKey = AdminString::safeKey($shiftColumnId);
        foreach ($columns as $index => $column) {
            if (($column['id'] ?? '') === $shiftKey) {
                $columns[$index]['shift'] = true;
                break;
            }
        }

        return $columns;
    }

    /**
     * @param array<int,array<string,mixed>> $columns
     * @return array<int,array<string,mixed>>
     */
    private function applyGroupingColumnAlign(array $columns, Grid $grid): array
    {
        $align = $grid->groupingAlign();
        $shiftColumnId = $grid->collapsibleShiftColumnId();
        if ($align === null || $align === '' || $shiftColumnId === null || $shiftColumnId === '') {
            return $columns;
        }

        $shiftKey = AdminString::safeKey($shiftColumnId);
        foreach ($columns as $index => $column) {
            if (($column['id'] ?? '') === $shiftKey) {
                $columns[$index]['align'] = $align;
                break;
            }
        }

        return $columns;
    }
}
