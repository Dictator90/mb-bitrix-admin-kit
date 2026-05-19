<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone\Handlers;

use Bitrix\Main\SiteTable;
use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Renderers\VisibilityWrapper;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\ItemAwareContract;
use MB\Bitrix\AdminKit\Contracts\UI\PageTypeAwareContract;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowRenderer;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;
use MB\Bitrix\AdminKit\Support\AdminKitJs;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

final class OptionsPageFormRenderer
{
    public function renderMultiSite(OptionsPage $page, string $moduleId): void
    {
        $sites = SiteTable::getList([
            'select' => ['LID', 'SITE_NAME', 'NAME'],
            'order' => ['SORT' => 'ASC'],
        ])->fetchAll();

        if ($sites === []) {
            $this->renderForm($page, $moduleId, '');

            return;
        }

        $currentSiteId = $page->request->get('site_id') ?: $sites[0]['LID'];

        echo '<div class="adminkit-sites-switcher">';
        foreach ($sites as $site) {
            $activeClass = ($site['LID'] === $currentSiteId) ? ' ui-btn-primary' : ' ui-btn-light-border';
            $siteName = htmlspecialcharsbx($site['SITE_NAME'] ?: $site['NAME'] ?: $site['LID']);
            $url = $page->buildSiteUrl($site['LID']);
            echo '<a href="' . $url . '" class="ui-btn' . $activeClass . '">' . $siteName . ' [' . $site['LID'] . ']</a> ';
        }
        echo '</div>';

        $this->renderForm($page, $moduleId, $currentSiteId);
    }

    public function renderForm(OptionsPage $page, string $moduleId, string $siteId): void
    {
        $action = $page->request->getRequestUri();
        $components = $page->applyRememberedTabs(iterator_to_array($page->components()));
        $wrapper = $page->buildOptionsWrapper($moduleId, $siteId, $components);
        $formId = 'adminkit-options-' . md5($page::class . $siteId);
        $activeTabId = $page->resolveRememberedActiveTabId($components);

        if ($page->wasSessidRejected()) {
            echo Alert::make(
                $page->message('MB_ADMIN_KIT_OPTIONS_SESSION_EXPIRED', 'Session expired. Refresh the page and try again.'),
                Alert::DANGER,
            )->render();
        }

        $allFields = $page->extractAllFields($components);
        $formData = [];
        foreach ($allFields as $field) {
            $formData[$field->getColumn()] = $wrapper->get($field->getColumn());
        }
        foreach ($allFields as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $field->{'applyDependency'}($formData);
            }
        }

        echo '<form id="' . $formId . '" method="POST" action="' . htmlspecialcharsbx($action) . '">';
        echo bitrix_sessid_post();
        echo '<input type="hidden" name="site_id" value="' . htmlspecialcharsbx($siteId) . '">';
        echo '<input type="hidden" name="adminkit_active_tab" value="' . htmlspecialcharsbx($activeTabId ?? '') . '">';

        $resolver = static fn (string $col) => $wrapper->get($col);

        echo '<div class="ui-form">';
        foreach ($components as $item) {
            if ($item instanceof Tab) {
                continue;
            }
            if ($item instanceof ComponentContract) {
                if ($item instanceof PageTypeAwareContract) {
                    $item = $item->withPageType(PageType::OPTIONS);
                }
                if ($item instanceof ItemAwareContract) {
                    $item = $item->withItem($wrapper);
                }
                $inner = $item->render();
                echo (new VisibilityWrapper())->wrap($inner, $item, new ComponentContext($wrapper, PageType::OPTIONS));
            } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::OPTIONS)) {
                $this->renderFieldRow($page, $item, $wrapper->get($item->getColumn()), $resolver);
            }
        }
        echo '</div>';

        echo '<div class="ui-button-panel adminkit-button-panel">';
        echo '<button type="submit" class="ui-btn ui-btn-success" id="' . $formId . '-submit">'
            . htmlspecialcharsbx($page->message('MB_ADMIN_KIT_OPTIONS_SAVE_BUTTON', 'Save'))
            . '</button>';
        echo '</div>';
        echo '</form>';

        $this->renderAjaxScript($page, $formId);
        $this->renderDependencyScript($page, $formId);
        $this->renderConditionalVisibilityScript($formId);
        $this->renderInlineCss();
        $this->renderHintInit();
    }

    public function renderFieldRow(OptionsPage $page, FieldContract $field, mixed $value, mixed $sourceValResolver = null): void
    {
        echo (new FieldRowRenderer())->render(new FieldRowContext(
            field: $field,
            value: $value,
            pageType: PageType::OPTIONS,
            sourceValueResolver: $sourceValResolver,
        ));
    }

    public function renderAjaxScript(OptionsPage $page, string $formId): void
    {
        AdminKitJs::renderInit('OptionsPage', [
            'formId' => $formId,
            'messages' => [
                'saved' => $page->message('MB_ADMIN_KIT_OPTIONS_SAVED', 'Settings saved'),
                'error' => $page->message('MB_ADMIN_KIT_OPTIONS_SAVE_ERROR', 'Save failed'),
            ],
        ]);
    }

    public function renderDependencyScript(OptionsPage $page, string $formId): void
    {
        $sourceCols = [];
        $dependsMap = [];

        foreach ($page->collectEditableFields() as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $dependsMap[$field->getColumn()] = $field->{'getDependsOn'}();
                foreach ($field->{'getDependsOn'}() as $col) {
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

    public function renderInlineCss(): void
    {
        echo <<<'CSS'
        <style>
        .adminkit-conditional-hidden { display: none !important; }
        .adminkit-visibility-wrapper.adminkit-conditional-hidden { display: none !important; }
        .adminkit-field-disabled { pointer-events: none; opacity: 0.42; filter: grayscale(20%); }
        .adminkit-field-loading { position: relative; pointer-events: none; min-height: 36px; }
        </style>
        CSS;
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
