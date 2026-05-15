<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

class DetailPage extends Page
{
    protected ?DataWrapper $item = null;

    public function __construct(ResourceContract $resource, mixed $id = null, array $params = [])
    {
        parent::__construct($resource, $id, $params);
        $this->id = $id;
    }

    public static function pageName(): string
    {
        return 'detail';
    }

    public function render(): void
    {
        global $APPLICATION;

        Extension::load(['ui', 'ui.layout-form', 'ui.buttons', 'ui.toolbar']);

        $row = $this->resource->findItem($this->id);
        $this->item = is_array($row)
            ? DataWrapper::fromArray($row, $this->resource->getPrimaryKey())
            : null;
        if (!$this->item) {
            echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">&#1069;&#1083;&#1077;&#1084;&#1077;&#1085;&#1090; &#1085;&#1077; &#1085;&#1072;&#1081;&#1076;&#1077;&#1085;</span></div>';
            return;
        }

        $APPLICATION->SetTitle($this->resource->getTitle() . ' #' . $this->id);

        $fields = $this->getVisibleFields();

        echo '<div class="ui-form">';

        foreach ($fields as $field) {
            $value = $field->resolveValue($this->item, $this->item->toArray());
            $label = htmlspecialcharsbx($field->getLabel());
            $displayValue = $field->renderDetail(new FieldRenderContext(
                field: $field,
                resource: $this->resource,
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

        $backAction = '(function(){' .
            'var topWindow=window.top||window;' .
            'var sidePanel=topWindow.BX&&topWindow.BX.SidePanel&&topWindow.BX.SidePanel.Instance;' .
            "var slider=sidePanel&&typeof sidePanel.getTopSlider==='function'?sidePanel.getTopSlider():null;" .
            "if(slider&&typeof slider.close==='function'){slider.close();return;}" .
            'window.history.back();' .
        '})();';

        (new ToolbarRenderer())->renderDetail($this->resource, $backAction, $this->editUrl());

        echo '<div class="ui-button-panel">';
        echo '<button type="button" class="ui-btn ui-btn-link" onclick="' . htmlspecialchars($backAction, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">&#1053;&#1072;&#1079;&#1072;&#1076;</button>';
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
    protected function fields(): iterable
    {
        return $this->resource->detailFields();
    }

    /**
     * @return FieldContract[]
     */
    protected function getVisibleFields(): array
    {
        $fields = [];
        foreach ($this->fields() as $field) {
            if ($field->isVisibleOn(PageType::DETAIL)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
