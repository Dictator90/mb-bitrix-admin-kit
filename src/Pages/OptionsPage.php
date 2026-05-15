<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use JsonException;
use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Support\AdminKitJs;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

/**
 * Standalone options page — saves values to `b_option` / `b_option_site`.
 *
 * Override components() and return any mix of fields, layout components, and Tabs.
 * Tab cannot render standalone — wrap it in Tabs::make([...]).
 *
 * Flat layout:
 *   protected function components(): iterable
 *   {
 *       return [
 *           Text::make('API Key', 'api_key'),
 *           Box::make('Debug', [
 *               Switcher::make('Enabled', 'debug'),
 *           ]),
 *       ];
 *   }
 *
 * Tabbed layout:
 *   protected function components(): iterable
 *   {
 *       return [
 *           Tabs::make([
 *               Tab::make('Основное', [
 *                   Text::make('API Key', 'api_key'),
 *               ])->active(),
 *
 *               Tab::make('Yandex Smart Captcha', [
 *                   Text::make('Client Key', 'ysc_client'),
 *                   Password::make('Server Key', 'ysc_server'),
 *               ])->id('ysc'),
 *           ]),
 *       ];
 *   }
 */
abstract class OptionsPage extends AbstractPage
{
    /** Module ID for Bitrix Config\Option — must be set in subclass. */
    protected string $moduleId = '';

    /** Show per-site tabs (one form per site in SiteTable). */
    protected bool $multiSite = false;

    protected HttpRequest $request;
    protected array $errors = [];
    private bool $sessidRejected = false;

    public function __construct()
    {
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
        }
        $this->request = Context::getCurrent()->getRequest();
    }

    /**
     * Return the page's components: fields, layout components, and/or tabs.
     *
     * @return iterable<FieldContract|ComponentContract>
     */
    public function fields(): iterable
    {
        return [];
    }

    protected function components(): iterable
    {
        $fields = $this->fields();
        if ($fields instanceof \Traversable) {
            return iterator_to_array($fields);
        }

        return $fields;
    }

    /** Whether to save/load options per-site. */
    protected function multiSite(): bool
    {
        return $this->multiSite;
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function render(): void
    {
        global $APPLICATION;

        (new AssetManager())->forForm()->addExtensions(['ui.layout-form', 'ui.hint', 'ui.alerts', 'ui.notification'])->load();

        $APPLICATION->SetTitle(static::getTitle());

        $this->renderToolbar();

        if (!$this->moduleId) {
            echo Alert::make($this->message('MB_ADMIN_KIT_OPTIONS_MODULE_ID_MISSING', 'moduleId is not configured.', [
                '#CLASS#' => static::class,
            ]), Alert::DANGER)->render();
            return;
        }

        if ($this->isPost()) {
            if (!check_bitrix_sessid()) {
                $this->rejectInvalidSessid();
            } elseif ($this->request->getPost('adminkit_action') === 'reactive') {
                $this->handleReactivePost($this->moduleId);
                return;
            } elseif ($this->isAjaxRequest()) {
                $this->handleAjaxPost($this->moduleId);
                return;
            } else {
                $this->handlePost($this->moduleId);
            }
        }

        if ($this->multiSite()) {
            $this->renderMultiSite($this->moduleId);
        } else {
            $this->renderOptionsForm($this->moduleId, '');
        }
    }

    /**
     * Renders the Bitrix admin toolbar — provides page title display,
     * breadcrumbs, and the "Add to favourites" star.
     */
    protected function renderToolbar(): void
    {
        global $APPLICATION;
        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    // ── POST handling ────────────────────────────────────────────────────

    protected function handlePost(string $moduleId): void
    {
        $siteId = $this->request->getPost('site_id') ?: '';
        $this->errors = $this->persistOptions($moduleId, $siteId);

        if ($this->errors === []) {
            $uri = $this->request->getRequestUri();
            $sep = str_contains($uri, '?') ? '&' : '?';
            LocalRedirect($uri . $sep . 'saved=1');
        }
    }

    /**
     * AJAX variant — saves options and returns JSON {status, message, errors}.
     * Called when the form is submitted with adminkit_ajax=Y.
     */
    protected function handleAjaxPost(string $moduleId): void
    {
        $siteId = $this->request->getPost('site_id') ?: '';
        $errors = $this->persistOptions($moduleId, $siteId);

        if ($errors === []) {
            $this->sendJsonAndExit([
                'status' => 'success',
                'message' => $this->message('MB_ADMIN_KIT_OPTIONS_SAVED', 'Settings saved'),
            ]);
        }

        $this->sendJsonAndExit([
            'status' => 'error',
            'message' => $this->message('MB_ADMIN_KIT_OPTIONS_SAVE_ERROR', 'Save failed'),
            'errors' => $errors,
        ]);
    }

    // ── Multi-site ───────────────────────────────────────────────────────

    protected function renderMultiSite(string $moduleId): void
    {
        $sites = SiteTable::getList([
            'select' => ['LID', 'SITE_NAME', 'NAME'],
            'order' => ['SORT' => 'ASC'],
        ])->fetchAll();

        if (empty($sites)) {
            $this->renderOptionsForm($moduleId, '');
            return;
        }

        $currentSiteId = $this->request->get('site_id') ?: $sites[0]['LID'];

        echo '<div class="adminkit-sites-switcher">';
        foreach ($sites as $site) {
            $activeClass = ($site['LID'] === $currentSiteId) ? ' ui-btn-primary' : ' ui-btn-light-border';
            $siteName = htmlspecialcharsbx($site['SITE_NAME'] ?: $site['NAME'] ?: $site['LID']);
            $url = $this->buildSiteUrl($site['LID']);
            echo '<a href="' . $url . '" class="ui-btn' . $activeClass . '">' . $siteName . ' [' . $site['LID'] . ']</a> ';
        }
        echo '</div>';

        $this->renderOptionsForm($moduleId, $currentSiteId);
    }

    // ── Form rendering ───────────────────────────────────────────────────

    protected function renderOptionsForm(string $moduleId, string $siteId): void
    {
        $action = $this->request->getRequestUri();
        $components = iterator_to_array($this->components());
        $wrapper = $this->buildOptionsWrapper($moduleId, $siteId, $components);
        $formId = 'adminkit-options-' . md5(static::class . $siteId);

        if ($this->sessidRejected) {
            echo Alert::make(
                $this->message('MB_ADMIN_KIT_OPTIONS_SESSION_EXPIRED', 'Session expired. Refresh the page and try again.'),
                Alert::DANGER,
            )->render();
        }

        // Apply field dependencies using current saved values so dependent fields
        // are rendered with the correct state (e.g. correct iblockId) on first load.
        $allFields = $this->extractAllFields($components);
        $formData = [];
        foreach ($allFields as $field) {
            $formData[$field->getColumn()] = $wrapper->get($field->getColumn());
        }
        foreach ($allFields as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $field->applyDependency($formData);
            }
        }

        echo '<form id="' . $formId . '" method="POST" action="' . htmlspecialcharsbx($action) . '">';
        echo bitrix_sessid_post();
        echo '<input type="hidden" name="site_id" value="' . htmlspecialcharsbx($siteId) . '">';
        echo '<input type="hidden" name="adminkit_ajax" value="Y">';

        $resolver = static fn (string $col) => $wrapper->get($col);

        echo '<div class="ui-form">';
        foreach ($components as $item) {
            if ($item instanceof Tab) {
                // Tab must be wrapped in Tabs::make([...]) — skip silently
                continue;
            }
            if ($item instanceof ComponentContract) {
                $inner = $item->withPageType(PageType::OPTIONS)->withItem($wrapper)->render();
                echo $this->wrapComponentWithVisibility($item, $inner, $resolver);
            } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::OPTIONS)) {
                $this->renderFieldRow($item, $wrapper->get($item->getColumn()), $resolver);
            }
        }
        echo '</div>';

        echo '<div class="ui-button-panel adminkit-button-panel">';
        echo '<button type="submit" class="ui-btn ui-btn-success" id="' . $formId . '-submit">'
            . htmlspecialcharsbx($this->message('MB_ADMIN_KIT_OPTIONS_SAVE_BUTTON', 'Save'))
            . '</button>';
        echo '</div>';
        echo '</form>';

        $this->renderAjaxScript($formId);
        $this->renderDependencyScript($formId);
        $this->renderConditionalVisibilityScript($formId);
        $this->renderInlineCss();
        $this->renderHintInit();
    }

    protected function renderInlineCss(): void
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

    protected function renderHintInit(): void
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

    /**
     * Renders the JS that intercepts submit, POSTs via fetch, and shows
     * BX.UI.Notification.Center toasts for success and error results.
     */
    protected function renderAjaxScript(string $formId): void
    {
        AdminKitJs::renderInit('OptionsPage', [
            'formId' => $formId,
            'messages' => [
                'saved' => $this->message('MB_ADMIN_KIT_OPTIONS_SAVED', 'Settings saved'),
                'error' => $this->message('MB_ADMIN_KIT_OPTIONS_SAVE_ERROR', 'Save failed'),
            ],
        ]);
    }

    /**
     * Render JS that watches source columns and toggles display of fields/components
     * that have visibleWhen() rules — pure CSS show/hide, no AJAX.
     */
    protected function renderConditionalVisibilityScript(string $formId): void
    {
        AdminKitJs::renderInit('Visibility', [
            'formId' => $formId,
        ]);
    }

    protected function renderFieldRow(FieldContract $field, mixed $value, mixed $sourceValResolver = null): void
    {
        $column = htmlspecialcharsbx($field->getColumn());
        $label = htmlspecialcharsbx($field->getLabel());
        $required = $field->isRequired() ? ' <span class="ui-ctl-required">*</span>' : '';
        $hint = method_exists($field, 'renderHint') ? $field->renderHint() : '';

        $visibilityAttr = '';
        $extraClass = '';
        if (method_exists($field, 'getVisibleWhen') && ($rule = $field->getVisibleWhen()) !== null) {
            $visibilityAttr = ' data-visible-when="' . htmlspecialcharsbx(json_encode($rule)) . '"';
            $sourceVal = is_callable($sourceValResolver) ? $sourceValResolver($rule['column']) : null;
            if (!$this->checkVisibilityRule($rule, $sourceVal)) {
                $extraClass = ' adminkit-conditional-hidden';
            }
        }

        echo '<div class="ui-form-row' . $extraClass . '" data-field-column="' . $column . '"' . $visibilityAttr . '>';
        echo '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . $required . $hint . '</div></div>';
        echo '<div class="ui-form-content">' . $field->renderFormField($value) . '</div>';
        echo '</div>';
    }

    protected function checkVisibilityRule(array $rule, mixed $currentValue): bool
    {
        if (isset($rule['values'])) {
            $str = (string)($currentValue ?? '');

            return in_array($str, $rule['values'], true);
        }

        $operator = $rule['operator'] ?? '=';
        $expected = $rule['value'] ?? null;

        if ($operator === 'in') {
            if (!is_array($expected)) {
                return false;
            }

            return in_array((string)($currentValue ?? ''), array_map('strval', $expected), true);
        }

        if ($operator === 'not in') {
            if (!is_array($expected)) {
                return true;
            }

            return !in_array((string)($currentValue ?? ''), array_map('strval', $expected), true);
        }

        $str = (string)($currentValue ?? '');
        $expectedStr = (string)($expected ?? '');

        return match ($operator) {
            '=', '==', '===' => $str === $expectedStr,
            '!=', '<>', '!==' => $str !== $expectedStr,
            default => $str === $expectedStr,
        };
    }

    protected function wrapComponentWithVisibility(
        ComponentContract $component,
        string $inner,
        mixed $sourceValResolver = null
    ): string {
        if (!method_exists($component, 'getVisibleWhen')) {
            return $inner;
        }
        $rule = $component->getVisibleWhen();
        if ($rule === null) {
            return $inner;
        }

        $json = htmlspecialcharsbx(json_encode($rule));
        $colVal = is_callable($sourceValResolver) ? $sourceValResolver($rule['column']) : null;
        $hidden = $this->checkVisibilityRule($rule, $colVal) ? '' : ' adminkit-conditional-hidden';

        return '<div data-visible-when="' . $json . '" class="adminkit-visibility-wrapper' . $hidden . '">' . $inner . '</div>';
    }

    /**
     * AJAX handler for dependsOn() field dependencies.
     * Applies dependency modifiers and returns re-rendered field HTML as JSON.
     */
    protected function handleReactivePost(string $moduleId): void
    {
        $fields = $this->collectAllFields();
        $siteId = $this->request->getPost('site_id') ?: '';
        $formData = [];

        foreach ($fields as $field) {
            $raw = $this->request->getPost($field->getColumn());
            if ($raw !== null) {
                $formData[$field->getColumn()] = $field->serializePostValue($raw);
                continue;
            }

            $stored = (string)Option::get($moduleId, $field->getColumn(), (string)($field->getDefault() ?? ''), $siteId);
            $formData[$field->getColumn()] = $stored !== ''
                ? $this->unserializeOptionValue($field, $stored)
                : $field->serializePostValue($field->getDefault());
        }

        $result = [];
        foreach ($fields as $field) {
            if (!method_exists($field, 'hasDependency') || !$field->hasDependency()) {
                continue;
            }
            $field->applyDependency($formData);
            // Pass null so the dependent field re-renders empty after its source changes;
            // the previously selected value is invalid for the new source value.
            $result[$field->getColumn()] = ['html' => $field->renderFormField(null)];
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'fields' => $result]);
        die();
    }

    /**
     * Render JS that watches source columns, shows disabled/loading states on dependent
     * fields, and re-renders them via AJAX when a source value changes.
     */
    protected function renderDependencyScript(string $formId): void
    {
        $sourceCols = [];
        $dependsMap = [];

        foreach ($this->collectAllFields() as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
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

    /**
     * Pre-load all option values into a DataWrapper so components can resolve them.
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     */
    protected function buildOptionsWrapper(string $moduleId, string $siteId, array $components): DataWrapper
    {
        $data = [];
        foreach ($this->extractAllFields($components) as $field) {
            $stored = (string)Option::get(
                $moduleId,
                $field->getColumn(),
                (string)($field->getDefault() ?? ''),
                $siteId,
            );
            $data[$field->getColumn()] = $stored !== ''
                ? $this->unserializeOptionValue($field, $stored)
                : $field->getDefault();
        }

        return new DataWrapper($data);
    }

    /**
     * @return list<string>
     */
    protected function persistOptions(string $moduleId, string $siteId): array
    {
        $errors = [];

        foreach ($this->collectAllFields() as $field) {
            $value = $field->serializePostValue($this->request->getPost($field->getColumn()));
            $fieldErrors = $field->runValidation($value);

            if ($fieldErrors !== []) {
                $errors = array_merge($errors, $fieldErrors);
                continue;
            }

            $this->persistOptionValue($moduleId, $field, $value, $siteId);
        }

        return $errors;
    }

    protected function persistOptionValue(string $moduleId, FieldContract $field, mixed $value, string $siteId): void
    {
        if (!$this->shouldPersistOptionValue($value)) {
            Option::delete($moduleId, ['name' => $field->getColumn(), 'site_id' => $siteId]);

            return;
        }

        Option::set($moduleId, $field->getColumn(), $this->serializeOptionValue($field, $value), $siteId);
    }

    protected function shouldPersistOptionValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return !(is_array($value) && $value === []);
    }

    protected function serializeOptionValue(FieldContract $field, mixed $value): string
    {
        if (method_exists($field, 'serializeOptionValue')) {
            return (string)$field->serializeOptionValue($value);
        }

        if (is_array($value)) {
            try {
                return (string)json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (JsonException) {
                return '[]';
            }
        }

        return (string)$value;
    }

    protected function unserializeOptionValue(FieldContract $field, string $value): mixed
    {
        if (method_exists($field, 'unserializeOptionValue')) {
            return $field->unserializeOptionValue($value);
        }

        if ($value !== '' && ($value[0] === '[' || $value[0] === '{')) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (JsonException) {
                // Keep scalar string when stored value is not valid JSON.
            }
        }

        return $value;
    }

    protected function rejectInvalidSessid(): void
    {
        $this->sessidRejected = true;

        if ($this->isAjaxRequest()) {
            $this->sendJsonAndExit([
                'status' => 'error',
                'message' => $this->message(
                    'MB_ADMIN_KIT_OPTIONS_SESSION_EXPIRED',
                    'Session expired. Refresh the page and try again.',
                ),
            ]);
        }
    }

    protected function isAjaxRequest(): bool
    {
        if ($this->request->getPost('adminkit_ajax') === 'Y') {
            return true;
        }

        return strtolower((string)$this->request->getHeader('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * @param array<string,mixed> $payload
     */
    protected function sendJsonAndExit(array $payload): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        die();
    }

    /**
     * @param array<string,string> $replace
     */
    protected function message(string $code, string $fallback, array $replace = []): string
    {
        $message = class_exists(Loc::class) ? (string)(Loc::getMessage($code) ?: $fallback) : $fallback;

        return $replace === [] ? $message : str_replace(array_keys($replace), array_values($replace), $message);
    }

    /**
     * Flatten all FieldContract instances from any depth of components/tabs.
     *
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     * @return FieldContract[]
     */
    protected function extractAllFields(array $components): array
    {
        $fields = [];
        foreach ($components as $item) {
            if ($item instanceof Tab) {
                $fields = array_merge($fields, $this->extractAllFields($item->getItems()));
            } elseif ($item instanceof ComponentContract) {
                $fields = array_merge($fields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $fields[] = $item;
            }
        }

        return $fields;
    }

    /** @return FieldContract[] — used by handlePost(); read-only fields are excluded */
    protected function collectAllFields(): array
    {
        $fields = $this->extractAllFields(iterator_to_array($this->components()));

        return array_values(array_filter($fields, fn (FieldContract $f) => !$f->isReadOnly()));
    }

    protected function buildSiteUrl(string $siteId): string
    {
        $uri = $this->request->getRequestUri();
        $parsed = parse_url($uri);
        parse_str($parsed['query'] ?? '', $query);
        $query['site_id'] = $siteId;
        unset($query['saved']);

        return ($parsed['path'] ?? '') . '?' . http_build_query($query);
    }

    protected function isPost(): bool
    {
        return $this->request->isPost();
    }
}
