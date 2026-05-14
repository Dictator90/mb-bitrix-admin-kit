<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\SiteTable;
use MB\Bitrix\AdminKit\Component\Layout;
use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Manager\AssetManager;
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

    public function __construct()
    {
        $this->request = Context::getCurrent()->getRequest();
    }

    /**
     * Return the page's components: fields, layout components, and/or tabs.
     *
     * @return iterable<FieldContract|ComponentContract|Tab>
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
            echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">moduleId не задан в ' . static::class . '</span></div>';
            return;
        }

        if ($this->isPost() && check_bitrix_sessid()) {
            if ($this->request->getPost('adminkit_action') === 'reactive') {
                $this->handleReactivePost($this->moduleId);
                return;
            }
            if ($this->request->getPost('adminkit_ajax') === 'Y') {
                $this->handleAjaxPost($this->moduleId);
                return;
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
        $fields = $this->collectAllFields();

        foreach ($fields as $field) {
            $value = $field->serializePostValue($this->request->getPost($field->getColumn()));
            $fieldErrors = $field->runValidation($value);

            if (!empty($fieldErrors)) {
                $this->errors = array_merge($this->errors, $fieldErrors);
                continue;
            }

            if ($value !== null && $value !== '') {
                Option::set($moduleId, $field->getColumn(), (string)$value, $siteId);
            } else {
                Option::delete($moduleId, ['name' => $field->getColumn(), 'site_id' => $siteId]);
            }
        }

        if (empty($this->errors)) {
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
        $fields = $this->collectAllFields();
        $errors = [];

        foreach ($fields as $field) {
            $value = $field->serializePostValue($this->request->getPost($field->getColumn()));
            $fieldErrors = $field->runValidation($value);

            if (!empty($fieldErrors)) {
                $errors = array_merge($errors, $fieldErrors);
                continue;
            }

            if ($value !== null && $value !== '') {
                Option::set($moduleId, $field->getColumn(), (string)$value, $siteId);
            } else {
                Option::delete($moduleId, ['name' => $field->getColumn(), 'site_id' => $siteId]);
            }
        }

        // Discard any admin-prolog output buffers so only our JSON is sent
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if (empty($errors)) {
            echo json_encode(['status' => 'success', 'message' => 'Настройки сохранены']);
        } else {
            echo json_encode(['status' => 'error', 'errors' => $errors]);
        }

        die();
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
        echo '<button type="submit" class="ui-btn ui-btn-success" id="' . $formId . '-submit">Сохранить</button>';
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
        $formIdJs = json_encode($formId);
        echo <<<HTML
        <script>
        BX.ready(function() {
            var form = document.getElementById({$formIdJs})
            if (!form) return;
            var submitBtn = document.getElementById({$formIdJs} + '-submit')

            function notify(content, isError) {
                BX.UI.Notification.Center.notify({
                    content: content,
                    autoHideDelay: isError ? 6000 : 4000,
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                submitBtn.disabled = true;
                submitBtn.classList.add('ui-btn-wait');

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('ui-btn-wait');

                    if (resp.status === 'success') {
                        notify(resp.message || 'Настройки сохранены', false);
                    } else {
                        var errors = resp.errors || [resp.message || 'Ошибка сохранения'];
                        notify(errors.join('<br>'), true);
                    }
                })
                .catch(function(err) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('ui-btn-wait');
                    notify('Ошибка запроса: ' + err.message, true);
                });
            });
        });
        </script>
        HTML;
    }

    /**
     * Render JS that watches source columns and toggles display of fields/components
     * that have visibleWhen() rules — pure CSS show/hide, no AJAX.
     */
    protected function renderConditionalVisibilityScript(string $formId): void
    {
        $formIdJs = json_encode($formId);

        echo <<<HTML
        <script>
        BX.ready(function() {
            var form = document.getElementById({$formIdJs})
            if (!form) return;

            function getFieldValue(col) {
                var inputs = form.querySelectorAll('[name="' + col + '"]');
                var fallback = '';
                for (var i = 0; i < inputs.length; i++) {
                    var el = inputs[i];
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.checked) return el.value;
                    } else if (el.type === 'hidden') {
                        if (fallback === '') fallback = el.value;
                    } else if (el.value !== '') {
                        return el.value;
                    }
                }
                if (fallback !== '') return fallback;
                // DialogSelector hidden inputs for multiple: name="col[]"
                var multi = form.querySelectorAll('[name="' + col + '[]"]');
                if (multi.length > 0) return multi[0].value;
                return '';
            }

            function matchesRule(rule, val) {
                if (rule.values) return rule.values.indexOf(val) !== -1;
                return val === rule.value;
            }

            function updateVisibility() {
                var els = form.querySelectorAll('[data-visible-when]');
                for (var i = 0; i < els.length; i++) {
                    var el = els[i];
                    var rule = JSON.parse(el.getAttribute('data-visible-when'));
                    var val  = getFieldValue(rule.column);
                    if (matchesRule(rule, val)) {
                        el.classList.remove('adminkit-conditional-hidden');
                    } else {
                        el.classList.add('adminkit-conditional-hidden');
                    }
                }
            }

            form.addEventListener('change', function() { updateVisibility(); });

            var visObserver = new MutationObserver(function() { updateVisibility(); });
            visObserver.observe(form, {childList: true, subtree: true});

            updateVisibility();
            setTimeout(updateVisibility, 900);
        });
        </script>
        HTML;
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
        $str = (string)($currentValue ?? '');
        if (isset($rule['values'])) {
            return in_array($str, $rule['values'], true);
        }
        return $str === ($rule['value'] ?? '');
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
            $formData[$field->getColumn()] = $raw !== null
                ? $field->serializePostValue($raw)
                : Option::get($moduleId, $field->getColumn(), (string)($field->getDefault() ?? ''), $siteId);
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
        $allFields = $this->collectAllFields();
        $sourceCols = [];
        $dependsMap = [];

        foreach ($allFields as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $dependsMap[$field->getColumn()] = $field->getDependsOn();
                foreach ($field->getDependsOn() as $col) {
                    $sourceCols[$col] = true;
                }
            }
        }

        if (empty($sourceCols)) {
            return;
        }

        $sourceColsJson = json_encode(array_keys($sourceCols));
        $dependsMapJson = json_encode($dependsMap);
        $formIdJs = json_encode($formId);

        echo <<<HTML
        <script>
        BX.ready(function() {
            var form = document.getElementById({$formIdJs})
            var sourceCols = {$sourceColsJson};
            var dependsMap = {$dependsMapJson};
            if (!form || !sourceCols.length) return;

            var initPhase = true;
            setTimeout(function() { initPhase = false; }, 800);

            function getSourceValue(srcCol) {
                var els = form.querySelectorAll('[name="' + srcCol + '"]');
                for (var i = 0; i < els.length; i++) {
                    if (els[i].value !== '') return els[i].value;
                }
                return '';
            }

            function sourcesHaveValues(col) {
                return (dependsMap[col] || []).every(function(s) {
                    return getSourceValue(s) !== '';
                });
            }

            function updateDisabledStates() {
                Object.keys(dependsMap).forEach(function(col) {
                    var row = form.querySelector('[data-field-column="' + col + '"]');
                    if (!row) return;
                    var content = row.querySelector('.ui-form-content');
                    if (!content || content.classList.contains('adminkit-field-loading')) return;
                    if (sourcesHaveValues(col)) {
                        content.classList.remove('adminkit-field-disabled');
                    } else {
                        content.classList.add('adminkit-field-disabled');
                    }
                });
            }

            updateDisabledStates();
            setTimeout(updateDisabledStates, 600);

            var debounceTimer = null;
            function triggerReactive() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    Object.keys(dependsMap).forEach(function(col) {
                        var row = form.querySelector('[data-field-column="' + col + '"]');
                        if (!row) return;
                        var content = row.querySelector('.ui-form-content');
                        if (content) {
                            content.classList.remove('adminkit-field-disabled');
                            content.classList.add('adminkit-field-loading');
                        }
                    });

                    var fd = new FormData(form);
                    fd.set('adminkit_action', 'reactive');

                    fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: fd,
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(resp) {
                        if (resp.status === 'success') {
                            Object.keys(resp.fields || {}).forEach(function(col) {
                                var row = form.querySelector('[data-field-column="' + col + '"]');
                                if (!row) return;
                                var content = row.querySelector('.ui-form-content');
                                if (!content) return;
                                content.classList.remove('adminkit-field-loading');
                                content.innerHTML = resp.fields[col].html;
                                content.querySelectorAll('script').forEach(function(s) {
                                    var ns = document.createElement('script');
                                    ns.textContent = s.textContent;
                                    document.head.appendChild(ns).parentNode.removeChild(ns);
                                });
                            });
                        }
                        updateDisabledStates();
                    })
                    .catch(function() { updateDisabledStates(); });
                }, 200);
            }

            sourceCols.forEach(function(col) {
                form.querySelectorAll('[name="' + col + '"]').forEach(function(el) {
                    el.addEventListener('change', triggerReactive);
                });
            });

            var observer = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var nodes = Array.prototype.slice.call(mutations[i].addedNodes)
                        .concat(Array.prototype.slice.call(mutations[i].removedNodes));
                    for (var j = 0; j < nodes.length; j++) {
                        var node = nodes[j];
                        if (node.nodeType === 1 && node.tagName === 'INPUT'
                                && node.type === 'hidden'
                                && sourceCols.indexOf(node.name) !== -1) {
                            if (initPhase) {
                                updateDisabledStates();
                            } else {
                                triggerReactive();
                            }
                            return;
                        }
                    }
                }
            });
            observer.observe(form, {childList: true, subtree: true});
        });
        </script>
        HTML;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Pre-load all option values into a DataWrapper so components can resolve them.
     * @param array<int, FieldContract|ComponentContract|Tab> $components
     */
    protected function buildOptionsWrapper(string $moduleId, string $siteId, array $components): DataWrapper
    {
        $data = [];
        foreach ($this->extractAllFields($components) as $field) {
            $data[$field->getColumn()] = Option::get(
                $moduleId,
                $field->getColumn(),
                (string)($field->getDefault() ?? ''),
                $siteId
            );
        }

        return new DataWrapper($data);
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
