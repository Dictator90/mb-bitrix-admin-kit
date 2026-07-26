<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Page\DetailPageContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

class DetailPage extends CrudPage implements DetailPageContract
{
    protected ?DataWrapper $item = null;

    public static function pageName(): string
    {
        return 'detail';
    }

    protected static function defaultPageType(): PageType
    {
        return PageType::DETAIL;
    }

    public function render(): void
    {
        global $APPLICATION;

        Extension::load(['ui', 'ui.layout-form', 'ui.buttons', 'ui.toolbar']);

        if (!$this->resource() instanceof ResourcePersistenceContract) {
            $this->renderError('Resource does not support persistence.');
            return;
        }

        $row = $this->resource()->findItem($this->id);
        $this->item = is_array($row)
            ? DataWrapper::fromArray($row, $this->resourcePrimaryKey())
            : null;

        if (!$this->item) {
            $this->renderError(
                LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_DETAIL_NOT_FOUND', 'Item not found.'),
            );

            return;
        }

        if (!$this->resource()->canView(new PermissionContext(resource: $this->resource(), operation: 'view', item: $row))) {
            $this->renderError(
                LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_DETAIL_ERR_CANNOT_VIEW', 'Insufficient permissions to view this record.'),
            );

            return;
        }

        $APPLICATION->SetTitle($this->resource()->getTitle() . ' #' . $this->id);

        // JS, закрывающий слайдер (боковую панель) или уходящий назад по истории.
        $backAction =
            '(function(){' .
            'var topWindow=window.top||window;' .
            'var sidePanel=topWindow.BX&&topWindow.BX.SidePanel&&topWindow.BX.SidePanel.Instance;' .
            "var slider=sidePanel&&typeof sidePanel.getTopSlider==='function'?sidePanel.getTopSlider():null;" .
            "if(slider&&typeof slider.close==='function'){slider.close();return;}" .
            'window.history.back();' .
            '})();';

        // ── 1. Тулбар ПЕРЕД полями ──────────────────────────────────────────
        (new ToolbarRenderer())->renderDetail($this->resource(), $backAction, $this->editUrl());

        $fields = $this->getVisibleFields();

        echo '<div class="ui-form ui-form-section">';

        foreach ($fields as $field) {
            $value = $field->resolveValue($this->item, $this->item->toArray());
            $label = htmlspecialcharsbx($field->getLabel());
            $displayValue = $field->renderDetail(new FieldRenderContext(
                field: $field,
                resource: $this->resource(),
                item: $this->item,
                value: $value,
                page: 'detail',
                row: $this->item->toArray(),
            ));

            echo '<div class="ui-form-row">';
            echo '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . '</div></div>';
            echo '<div class="ui-form-content"><div class="ui-text-body">' . $displayValue . '</div></div>';
            echo '</div>';
        }

        echo '</div>';
    }

    protected function editUrl(): ?string
    {
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $parsed = parse_url($requestUri);
        parse_str((string)($parsed['query'] ?? ''), $query);
        $query['action'] = 'edit';
        $query['id'] = (string)$this->id;
        $path = (string)($parsed['path'] ?? '');
        if ($path === '') {
            return null;
        }

        return $path . '?' . http_build_query($query);
    }

    /** @return iterable<FieldContract> */
    public function fields(): iterable
    {
        return $this->resource()->detailFields();
    }
}
