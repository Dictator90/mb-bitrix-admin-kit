<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Component\Notification;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Contracts\Page\DetailPageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

class DetailPage extends CrudPage implements DetailPageContract
{
    protected ?DataWrapper $item = null;

    public function __construct(?ResourceContract $resource = null, mixed $id = null, array $params = [])
    {
        parent::__construct($resource, $id, $params);
        $this->pageType = PageType::DETAIL;
        $this->id = $id;
    }

    public static function pageName(): string
    {
        return 'detail';
    }

    public function render(): void
    {
        global $APPLICATION;

        Loc::loadMessages(__FILE__);

        Extension::load(['ui', 'ui.layout-form', 'ui.buttons', 'ui.toolbar']);

        if (!$this->resource instanceof ResourcePersistenceContract) {
            echo Notification::alert('Resource does not support persistence.', Notification::TYPE_WARNING);
            return;
        }

        $row = $this->resource->findItem($this->id);
        $this->item = is_array($row)
            ? DataWrapper::fromArray($row, $this->resource->getPrimaryKey())
            : null;

        if (!$this->item) {
            echo Notification::alert(
                $this->message('MB_ADMIN_KIT_DETAIL_NOT_FOUND', 'Элемент не найден.'),
                Notification::TYPE_WARNING,
            );

            return;
        }

        if (!$this->resource->canView(new PermissionContext(resource: $this->resource, operation: 'view', item: $row))) {
            echo Notification::alert(
                $this->message('MB_ADMIN_KIT_DETAIL_ERR_CANNOT_VIEW', 'Недостаточно прав для просмотра записи.'),
                Notification::TYPE_WARNING,
            );

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

        $backLabel = htmlspecialcharsbx($this->message('MB_ADMIN_KIT_DETAIL_BACK', 'Назад'));
        echo '<div class="ui-button-panel">';
        echo '<button type="button" class="ui-btn ui-btn-link" onclick="' . htmlspecialchars($backAction, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . $backLabel . '</button>';
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

    private function message(string $key, string $fallback): string
    {
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);

            return (string)(Loc::getMessage($key) ?: $fallback);
        }

        return $fallback;
    }
}
