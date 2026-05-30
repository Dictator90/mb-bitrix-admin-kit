<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use Bitrix\Main\Localization\Loc;
use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Component\Renderers\VisibilityWrapper;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\ItemAwareContract;
use MB\Bitrix\AdminKit\Contracts\UI\PageTypeAwareContract;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowRenderer;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Support\AdminKitJs;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

final class FormPageFormRenderer
{
    public function render(FormPage $page): void
    {
        $this->renderAlerts($page);
        if (!$page->getIsEditNotFound()) {
            $this->renderForm($page);
            $this->renderDependencyScript($page, $page->getFormId());
            $this->renderConditionalVisibilityScript($page->getFormId());
            if ($page->isAsync()) {
                $this->renderAsyncSaveScript($page);
            }
        }
        $this->renderHintInit();
    }

    public function renderAlerts(FormPage $page): void
    {
        if ($page->hasValidationErrors()) {
            echo '<div class="ui-alert ui-alert-danger adminkit-alert">';
            echo '<span class="ui-alert-message">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_VALIDATION_ERROR')) . '</span>';
            echo '</div>';
        }

        if ($page->getGlobalErrors() !== []) {
            echo '<div class="ui-alert ui-alert-danger adminkit-alert">';
            foreach ($page->getGlobalErrors() as $error) {
                $this->renderGlobalErrorMessage((string)$error);
            }
            echo '</div>';
        }

        if ($page->request->get('saved') === '1' || $page->getShowSavedNotice()) {
            echo '<div class="ui-alert ui-alert-success adminkit-alert"><span class="ui-alert-message">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_SAVED')) . '</span></div>';
        }
    }

    protected function renderGlobalErrorMessage(string $error): void
    {
        if (str_contains($error, "\n")) {
            echo '<pre class="adminkit-error-trace ui-alert-message">' . htmlspecialcharsbx($error) . '</pre>';
            return;
        }
        echo '<span class="ui-alert-message">' . htmlspecialcharsbx($error) . '</span><br>';
    }

    public function renderForm(FormPage $page): void
    {
        $action = $page->request->getRequestUri();
        $tabs = iterator_to_array($page->getTabsList());
        $fid = htmlspecialcharsbx($page->getFormId());

        echo '<form id="' . $fid . '" method="POST" action="' . htmlspecialcharsbx($action) . '">';
        echo bitrix_sessid_post();

        if ($tabs !== []) {
            $this->renderTabbedForm($page, $tabs);
        } else {
            $this->renderFlatForm($page);
        }

        $this->renderButtons($page);
        echo '</form>';
    }

    public function renderFlatForm(FormPage $page): void
    {
        $items = $page->getVisibleItemsList();
        $page->applyInitialDependenciesList($items);

        echo '<div class="ui-form">';
        foreach ($items as $item) {
            if ($item instanceof ComponentContract) {
                if ($item instanceof PageTypeAwareContract) {
                    $item = $item->withPageType(PageType::FORM);
                }
                if ($item instanceof ItemAwareContract) {
                    $item = $item->withItem($page->getItemValue());
                }
                $inner = $item->render();
                echo (new VisibilityWrapper())->wrap($inner, $item, new ComponentContext($page->getItemValue(), PageType::FORM));
            } elseif ($item instanceof FieldContract) {
                $this->renderFormRow($page, $item, $page->resolveFieldValueForField($item));
            }
        }
        echo '</div>';
    }

    /** @param array<int,Tab> $tabs */
    public function renderTabbedForm(FormPage $page, array $tabs): void
    {
        $allTabItems = [];
        foreach ($tabs as $tab) {
            foreach ($tab->getItems() as $item) {
                $allTabItems[] = $item;
            }
        }
        $page->applyInitialDependenciesList($allTabItems);

        echo Tabs::make($tabs)
            ->withItem($page->getItemValue())
            ->withPageType(PageType::FORM)
            ->render();
    }

    public function renderFormRow(FormPage $page, FieldContract $field, mixed $value): void
    {
        $resource = $page->getResource();
        /** @var \MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract $resource */
        echo (new FieldRowRenderer())->render(new FieldRowContext(
            field: $field,
            value: $value,
            item: $page->getItemValue(),
            pageType: PageType::FORM,
            renderContext: new FieldRenderContext(
                field: $field,
                resource: $resource,
                item: $page->getItemValue(),
                value: $value,
                page: 'form',
                row: $page->getItemValue()?->toArray() ?? [],
                errors: $page->getFieldErrors()[$field->getColumn()] ?? [],
                meta: [
                    'mode' => $page->getFormMode(),
                    'formData' => $page->formConditionContextList(),
                ],
            ),
            errors: $page->getFieldErrors()[$field->getColumn()] ?? [],
        ));
    }

    public function renderButtons(FormPage $page): void
    {
        $cancelAction = $page->getCancelActionJs();

        echo '<div class="ui-button-panel adminkit-button-panel">';
        echo '<button type="submit" class="ui-btn ui-btn-success" id="'.$page->getFormId().'-submit" name="save" value="Y">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_SAVE')) . '</button>';
        echo '<button type="button" class="ui-btn ui-btn-link" onclick="' . htmlspecialcharsbx(
            $cancelAction
        ) . '">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_CANCEL')) . '</button>';
        echo '</div>';
    }

    public function renderDependencyScript(FormPage $page, string $formId): void
    {
        $sourceCols = [];
        $dependsMap = [];

        foreach ($page->collectAllFields() as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency() && method_exists($field, 'getDependsOn')) {
                /** @var mixed $field */
                $dependsMap[$field->getColumn()] = $field->getDependsOn();
                foreach ($field->getDependsOn() as $col) {
                    $sourceCols[$col] = true;
                }
            }
        }

        if ($sourceCols === []) {
            return;
        }

        AdminKitJs::renderInit('Dependencies', [
            'formId' => $formId,
            'sourceCols' => array_keys($sourceCols),
            'dependsMap' => $dependsMap,
        ]);
    }

    public function renderConditionalVisibilityScript(string $formId): void
    {
        AdminKitJs::renderInit('Visibility', [
            'formId' => $formId,
        ]);
    }

    public function renderAsyncSaveScript(FormPage $page): void
    {
        $resource = $page->getResource();
        /** @var \MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract $resource */
        AdminKitJs::renderInit('Form', [
            'formId' => $page->getFormId(),
            'gridId' => $resource->getGridId(),
            'messages' => [
                'validationError' => (string)Loc::getMessage('MB_ADMIN_KIT_FORM_VALIDATION_ERROR'),
                'saveFailed' => (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_SAVE_FAILED'),
                'saved' => (string)Loc::getMessage('MB_ADMIN_KIT_FORM_SAVED'),
            ],
        ]);
    }

    public function renderHintInit(): void
    {
        echo <<<'HTML'
        <script>
        BX.ready(function() {
            if (BX.UI && BX.UI.Hint) {
                BX.UI.Hint.init(document.body);
            }
        });
        </script>
        HTML;
    }
}
