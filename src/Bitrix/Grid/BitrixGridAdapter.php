<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Bitrix\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Support\AdminCollection;

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
            'SHOW_ROW_CHECKBOXES' => $hasActions,
            'SHOW_CHECK_ALL_CHECKBOXES' => $hasActions,
            'SHOW_ACTION_PANEL' => $hasActions,
            'ACTION_PANEL' => $hasActions ? $this->actionPanelAdapter->componentParams($grid) : null,
            'ALLOW_INLINE_EDIT' => $inlineEditable,
            'ALLOW_EDIT_SELECTION' => $inlineEditable,
            'ALLOW_COLUMNS_SORT' => true,
            'ALLOW_COLUMNS_RESIZE' => true,
            'ALLOW_HORIZONTAL_SCROLL' => true,
            'ALLOW_ROWS_SORT' => false,
            'AJAX_MODE' => 'Y',
            'AJAX_OPTION_HISTORY' => 'N',
            'AJAX_OPTION_JUMP' => 'N',
        ];

        if (!$hasActions) {
            unset($params['ACTION_PANEL']);
        }

        return $params;
    }
}
