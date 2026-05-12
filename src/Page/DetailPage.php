<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

class DetailPage extends Page
{
    protected int $id;
    protected ?DataWrapper $item = null;

    public function __construct(ResourceContract $resource, int $id)
    {
        parent::__construct($resource);
        $this->id = $id;
    }

    public function render(): void
    {
        global $APPLICATION;

        Extension::load(['ui', 'ui.layout-form', 'ui.buttons']);

        $this->item = $this->resource->findItem($this->id);
        if (!$this->item) {
            echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">Элемент не найден</span></div>';
            return;
        }

        $APPLICATION->SetTitle($this->resource->getTitle() . ' #' . $this->id);

        $fields = $this->getVisibleFields();

        echo '<div class="ui-form">';

        foreach ($fields as $field) {
            $value = $this->item->get($field->getColumn());
            $label = htmlspecialcharsbx($field->getLabel());
            $displayValue = htmlspecialcharsbx((string)$field->previewValue($value));

            echo '<div class="ui-form-row">';
            echo '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . '</div></div>';
            echo '<div class="ui-form-content"><div class="ui-text-body">' . $displayValue . '</div></div>';
            echo '</div>';
        }

        echo '</div>';

        echo '<div class="ui-button-panel">';
        echo '<button type="button" class="ui-btn ui-btn-link" onclick="window.history.back()">Назад</button>';
        echo '</div>';
    }

    /**
     * @return FieldContract[]
     */
    protected function getVisibleFields(): array
    {
        $fields = [];
        foreach ($this->resource->detailFields() as $field) {
            if ($field->isVisibleOn(PageType::DETAIL)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
