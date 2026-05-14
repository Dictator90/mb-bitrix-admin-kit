<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Exceptions\AdminKitException;
use MB\Bitrix\AdminKit\Exceptions\PermissionDeniedException;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\Tab;

class FormPage extends Page
{
    protected ?int $id;
    protected ?DataWrapper $item = null;
    protected array $errors = [];

    /** @var array<string,string[]> */
    protected array $fieldErrors = [];

    /** Set to true after a successful save inside a SidePanel (skips redirect, closes panel). */
    protected bool $savedInSidePanel = false;

    protected string $formId = '';

    public function __construct(ResourceContract $resource, ?int $id = null)
    {
        parent::__construct($resource);
        $this->id = $id;
    }

    public function render(): void
    {
        global $APPLICATION;

        $inPanel = $this->isSidePanelMode();

        Extension::load(['ui', 'ui.layout-form', 'ui.buttons', 'ui.hint', 'mb.ui.tabs']);

        if ($inPanel) {
            Extension::load(['sidepanel']);
        }

        $title = $this->id
            ? $this->resource->getTitle() . ' — Редактирование #' . $this->id
            : $this->resource->getTitle() . ' — Создание';
        $APPLICATION->SetTitle($title);

        $this->formId = 'adminkit-form-' . md5(static::class . ($this->id ?? ''));

        if ($this->id) {
            $row = $this->resource->findItem($this->id);
            $this->item = $row ? DataWrapper::fromArray($row, $this->resource->getPrimaryKey()) : null;
            if (!$this->resource->canView(new PermissionContext(resource: $this->resource, operation: 'view', item: $row))) {
                $this->errors[] = 'Недостаточно прав для просмотра записи.';
            }
            if (!$this->resource->canUpdate(new PermissionContext(resource: $this->resource, operation: 'update', item: $row))) {
                $this->errors[] = 'Недостаточно прав для редактирования записи.';
            }
        } elseif (!$this->resource->canCreate(new PermissionContext(resource: $this->resource, operation: 'create'))) {
            $this->errors[] = 'Недостаточно прав для создания записи.';
        }

        if ($this->isPost() && check_bitrix_sessid()) {
            if ($this->request->getPost('adminkit_action') === 'reactive') {
                $this->handleReactivePost();
                return;
            }
            $this->handlePost();
        }

        // Successful save inside SidePanel: close the slider from the iframe context.
        // window.top.BX is used because BX inside an iframe is the iframe's own context —
        // the SidePanel instance lives in the parent (top) window.
        if ($this->savedInSidePanel) {
            echo '<script>window.top.BX.SidePanel.Instance.getTopSlider().close();</script>';
            return;
        }

        $this->renderAlerts();
        $this->renderForm();
        $this->renderReactiveScript();
        $this->renderDependencyScript($this->formId);
        $this->renderConditionalVisibilityScript($this->formId);
        $this->renderHintInit();
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

    /** True when the page is rendered inside a Bitrix SidePanel. */
    protected function isSidePanelMode(): bool
    {
        return $this->request->get('IFRAME') === 'Y';
    }

    protected function handlePost(): void
    {
        $fields = $this->collectAllFields();
        $raw = [];

        foreach ($fields as $field) {
            $column = $field->getColumn();
            $raw[$column] = $this->request->getPost($column);
        }

        $formData = (new DataPipeline())->process($fields, $raw);
        foreach ($formData->errors() as $column => $messages) {
            foreach ($messages as $message) {
                $this->fieldErrors[$column][] = $message;
                $this->errors[] = $message;
            }
        }

        $context = new DbOperationContext(
            resource: $this->resource,
            operation: $this->id ? 'update' : 'create',
            itemId: $this->id,
            oldData: $this->item?->toArray() ?? [],
            newData: $formData->validated(),
            rawData: $formData->raw(),
            normalizedData: $formData->normalized(),
            validatedData: $formData->validated(),
            request: $this->request,
        );

        try {
            $this->assertSavePermission($context);
            $this->resource->beforeValidate($formData, $context);

            if ($formData->hasErrors()) {
                return;
            }

            $this->resource->afterValidate($formData, $context);

            if ($this->id) {
                $result = $this->resource->updateItemResult($this->id, $formData, $context);
                $savedId = $result->isSuccess() ? $this->id : null;
            } else {
                $result = $this->resource->createItemResult($formData, $context);
                $savedId = $result->isSuccess() ? $result->id() : null;
            }
        } catch (AdminKitException $exception) {
            $this->errors[] = $exception->getMessage();
            return;
        }

        if (!$result->isSuccess()) {
            foreach ($result->errors() as $error) {
                $this->errors[] = $error;
            }
            return;
        }

        if ($savedId) {
            if ($this->isSidePanelMode()) {
                // Don't redirect — signal the slider to close; grid reload happens in onCloseComplete
                $this->savedInSidePanel = true;
            } else {
                $backUrl = $this->request->getPost('back_url') ?: $this->request->getRequestUri();
                $sep = str_contains($backUrl, '?') ? '&' : '?';
                $this->redirect($backUrl . $sep . 'saved=1');
            }
        }
    }

    protected function assertSavePermission(DbOperationContext $context): void
    {
        $permission = new PermissionContext(
            resource: $this->resource,
            operation: $context->operation,
            item: $context->oldData ?: null,
        );

        if ($this->id && !$this->resource->canUpdate($permission)) {
            throw new PermissionDeniedException('Недостаточно прав для сохранения записи.');
        }

        if (!$this->id && !$this->resource->canCreate($permission)) {
            throw new PermissionDeniedException('Недостаточно прав для создания записи.');
        }
    }

    protected function renderAlerts(): void
    {
        if (!empty($this->errors)) {
            echo '<div class="ui-alert ui-alert-danger adminkit-alert">';
            foreach ($this->errors as $error) {
                echo '<span class="ui-alert-message">' . htmlspecialcharsbx($error) . '</span><br>';
            }
            echo '</div>';
        }

        if ($this->request->get('saved') === '1') {
            echo '<div class="ui-alert ui-alert-success adminkit-alert"><span class="ui-alert-message">Данные сохранены</span></div>';
        }
    }

    protected function renderForm(): void
    {
        $action = $this->request->getRequestUri();
        $tabs = iterator_to_array($this->resource->formTabs());
        $fid = htmlspecialcharsbx($this->formId);

        echo '<form id="' . $fid . '" method="POST" action="' . htmlspecialcharsbx($action) . '">';
        echo bitrix_sessid_post();

        if (!empty($tabs)) {
            $this->renderTabbedForm($tabs);
        } else {
            $this->renderFlatForm();
        }

        $this->renderButtons();
        echo '</form>';
    }

    protected function renderFlatForm(): void
    {
        $items = $this->getVisibleItems();
        $this->applyInitialDependencies($items);

        echo '<div class="ui-form ui-form-section">';

        foreach ($items as $item) {
            if ($item instanceof ComponentContract) {
                $inner = $item->withPageType(PageType::FORM)->withItem($this->item)->render();
                echo $this->wrapComponentWithVisibility($item, $inner);
            } elseif ($item instanceof FieldContract) {
                $value = $this->item?->get($item->getColumn());
                $this->renderFormRow($item, $value);
            }
        }
        echo '</div>';
    }

    /** @param Tab[] $tabs */
    protected function renderTabbedForm(array $tabs): void
    {
        $hasActive = false;
        foreach ($tabs as $tab) {
            if ($tab->isActive()) {
                $hasActive = true;
                break;
            }
        }
        if (!$hasActive && !empty($tabs)) {
            $tabs[0]->active();
        }

        // Collect all tab items and apply dependencies before rendering
        $allTabItems = [];
        foreach ($tabs as $tab) {
            foreach ($tab->getItems() as $item) {
                $allTabItems[] = $item;
            }
        }
        $this->applyInitialDependencies($allTabItems);

        echo '<div class="ui-tabs" id="adminkit-form-tabs">';
        echo '<div class="ui-tabs-nav">';
        foreach ($tabs as $tab) {
            $activeClass = $tab->isActive() ? ' ui-tabs-nav-item-active' : '';
            $tabId = htmlspecialcharsbx('tab_' . $tab->getId());
            $title = htmlspecialcharsbx($tab->getTitle());
            echo '<a class="ui-tabs-nav-item' . $activeClass . '" href="#' . $tabId . '">' . $title . '</a>';
        }
        echo '</div>';

        foreach ($tabs as $tab) {
            $activeClass = $tab->isActive() ? ' ui-tabs-panel-active' : '';
            $tabId = htmlspecialcharsbx('tab_' . $tab->getId());

            echo '<div id="' . $tabId . '" class="ui-tabs-panel' . $activeClass . '">';
            echo '<div class="ui-form">';

            foreach ($tab->getItems() as $item) {
                if ($item instanceof ComponentContract) {
                    $inner = $item->withPageType(PageType::FORM)->withItem($this->item)->render();
                    echo $this->wrapComponentWithVisibility($item, $inner);
                } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::FORM)) {
                    $value = $this->item?->get($item->getColumn());
                    $this->renderFormRow($item, $value);
                }
            }

            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '<script>BX.ready(function(){ if(MB.UI && MB.UI.Tabs) { new MB.UI.Tabs({node: BX("adminkit-form-tabs")}); } });</script>';
    }

    protected function renderFormRow(FieldContract $field, mixed $value): void
    {
        $column = htmlspecialcharsbx($field->getColumn());
        $label = htmlspecialcharsbx($field->getLabel());
        $requiredMark = $field->isRequired() ? ' <span class="ui-ctl-required">*</span>' : '';
        $hint = method_exists($field, 'renderHint') ? $field->renderHint() : '';

        $visibilityAttr = '';
        $extraClass = '';
        if (method_exists($field, 'getVisibleWhen') && ($rule = $field->getVisibleWhen()) !== null) {
            $visibilityAttr = ' data-visible-when="' . htmlspecialcharsbx(json_encode($rule)) . '"';
            $sourceVal = $this->item?->get($rule['column']);
            if (!$this->checkVisibilityRule($rule, $sourceVal)) {
                $extraClass = ' adminkit-conditional-hidden';
            }
        }

        echo '<div class="ui-form-row' . $extraClass . '" data-field-column="' . $column . '"' . $visibilityAttr . '>';
        echo '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . $requiredMark . $hint . '</div></div>';
        echo '<div class="ui-form-content">';
        echo $field->renderFormField($value);
        foreach ($this->fieldErrors[$field->getColumn()] ?? [] as $message) {
            echo '<div class="ui-alert ui-alert-danger adminkit-field-error"><span class="ui-alert-message">' . htmlspecialcharsbx($message) . '</span></div>';
        }
        echo '</div>';
        echo '</div>';
    }

    protected function checkVisibilityRule(array $rule, mixed $currentValue): bool
    {
        $str = (string)($currentValue ?? '');
        if (isset($rule['values'])) {
            return in_array($str, $rule['values'], true);
        }
        $operator = $rule['operator'] ?? '=';
        $expected = (string)($rule['value'] ?? '');

        return match ($operator) {
            '=', '==', '===' => $str === $expected,
            '!=', '<>', '!==' => $str !== $expected,
            default => $str === $expected,
        };
    }

    protected function wrapComponentWithVisibility(ComponentContract $component, string $inner): string
    {
        if (!method_exists($component, 'getVisibleWhen')) {
            return $inner;
        }
        $rule = $component->getVisibleWhen();
        if ($rule === null) {
            return $inner;
        }

        $json = htmlspecialcharsbx(json_encode($rule));
        $colVal = $this->item?->get($rule['column']);
        $hidden = $this->checkVisibilityRule($rule, $colVal) ? '' : ' adminkit-conditional-hidden';

        return '<div data-visible-when="' . $json . '" class="adminkit-visibility-wrapper' . $hidden . '">' . $inner . '</div>';
    }

    protected function renderButtons(): void
    {
        $cancelAction = $this->isSidePanelMode()
            ? 'window.top.BX.SidePanel.Instance.getTopSlider().close()'
            : 'window.history.back()';

        echo '<div class="ui-button-panel adminkit-button-panel">';
        echo '<button type="submit" class="ui-btn ui-btn-success" name="save" value="Y">Сохранить</button>';
        echo '<button type="button" class="ui-btn ui-btn-link" onclick="' . htmlspecialcharsbx(
            $cancelAction
        ) . '">Отмена</button>';
        echo '</div>';
    }

    /** @return array<int, FieldContract|ComponentContract> */
    protected function getVisibleItems(): array
    {
        $items = [];
        foreach ($this->resource->formFields() as $item) {
            if ($item instanceof ComponentContract) {
                $items[] = $item;
            } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::FORM)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /** @return FieldContract[] */
    protected function getVisibleFields(): array
    {
        $fields = [];
        foreach ($this->getVisibleItems() as $item) {
            if ($item instanceof ComponentContract) {
                $fields = array_merge($fields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $fields[] = $item;
            }
        }
        return $fields;
    }

    /**
     * Apply dependsOn() modifiers to all fields in $items using the current item's saved values.
     * Mutates field instances in-place so the subsequent render loop sees correct state.
     *
     * @param array<int, FieldContract|ComponentContract> $items
     */
    protected function applyInitialDependencies(array $items): void
    {
        $allFields = [];
        foreach ($items as $item) {
            if ($item instanceof ComponentContract) {
                $allFields = array_merge($allFields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $allFields[] = $item;
            }
        }

        $formData = [];
        foreach ($allFields as $field) {
            $formData[$field->getColumn()] = $this->item?->get($field->getColumn());
        }

        foreach ($allFields as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $field->applyDependency($formData);
            }
        }
    }

    protected function renderReactiveScript(): void
    {
        $allFields = $this->collectAllFields();
        $hasReactive = false;

        foreach ($allFields as $field) {
            if (method_exists($field, 'isReactive') && $field->isReactive()) {
                $hasReactive = true;
                break;
            }
        }

        if (!$hasReactive) {
            return;
        }

        $uri = $this->request->getRequestUri();
        $path = ($pos = strpos($uri, '?')) !== false ? substr($uri, 0, $pos) : $uri;
        $reactiveUrl = htmlspecialcharsbx($path . '?action=adminkit_reactive');

        echo <<<HTML
        <script>
        BX.ready(function() {
            document.querySelectorAll('[data-reactive="1"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    var field = el.dataset.reactiveField;
                    var targets = JSON.parse(el.dataset.reactiveTargets || '[]');
                    var url = el.dataset.reactiveUrl || '{$reactiveUrl}';
                    var formEl = el.closest('form');
                    var formData = formEl ? new FormData(formEl) : new FormData();
                    var allData = {};
                    formData.forEach(function(v, k) { allData[k] = v; });

                    BX.ajax.runAction && BX.ajax || fetch(url, {
                        method: 'POST',
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        body: new URLSearchParams({
                            action: 'adminkit_reactive',
                            field: field,
                            value: el.value,
                            sessid: BX.bitrix_sessid ? BX.bitrix_sessid() : '',
                            data: JSON.stringify(allData),
                        })
                    }).then(function(r) { return r.json(); })
                      .then(function(resp) {
                        if (resp.status !== 'success') return;
                        targets.forEach(function(targetCol) {
                            if (resp.data[targetCol] === undefined) return;
                            var targetEl = document.querySelector('[name="' + targetCol + '"]');
                            if (!targetEl) return;
                            var newVal = resp.data[targetCol];
                            if (Array.isArray(newVal)) {
                                if (targetEl.tagName === 'SELECT') {
                                    var prevVal = targetEl.value;
                                    targetEl.innerHTML = '';
                                    newVal.forEach(function(opt) {
                                        var o = document.createElement('option');
                                        o.value = typeof opt === 'object' ? opt.value : opt;
                                        o.textContent = typeof opt === 'object' ? opt.label : opt;
                                        if (o.value == prevVal) o.selected = true;
                                        targetEl.appendChild(o);
                                    });
                                }
                            } else {
                                targetEl.value = newVal;
                            }
                        });
                    });
                });
            });
        });
        </script>
        HTML;
    }

    /**
     * AJAX handler for dependsOn() field dependencies.
     * Applies dependency modifiers and returns re-rendered field HTML as JSON.
     */
    protected function handleReactivePost(): void
    {
        $fields = $this->collectAllFields();
        $formData = [];

        foreach ($fields as $field) {
            $formData[$field->getColumn()] = $field->serializePostValue($this->request->getPost($field->getColumn()));
        }

        if ($this->id && $this->item === null) {
            $row = $this->resource->findItem($this->id);
            $this->item = $row ? DataWrapper::fromArray($row, $this->resource->getPrimaryKey()) : null;
        }

        $result = [];
        foreach ($fields as $field) {
            if (!method_exists($field, 'hasDependency') || !$field->hasDependency()) {
                continue;
            }
            $field->applyDependency($formData);
            $value = $formData[$field->getColumn()] ?? $this->item?->get($field->getColumn());
            $result[$field->getColumn()] = ['html' => $field->renderFormField($value)];
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

            // During the first 800 ms the DialogSelector may be creating hidden inputs
            // for pre-selected values — treat those mutations as init, not user input.
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

            // Delegate change events on the whole form
            form.addEventListener('change', function(e) {
                updateVisibility();
            });

            // Watch hidden inputs added by DialogSelector
            var visObserver = new MutationObserver(function() {
                updateVisibility();
            });
            visObserver.observe(form, {childList: true, subtree: true});

            // Initial evaluation (after a tick to let DialogSelector init)
            updateVisibility();
            setTimeout(updateVisibility, 900);
        });
        </script>
        HTML;
    }

    /** @return FieldContract[] — all writable fields from both flat form and tabs */
    protected function collectAllFields(): array
    {
        $tabs = iterator_to_array($this->resource->formTabs());
        if (!empty($tabs)) {
            $fields = [];
            foreach ($tabs as $tab) {
                foreach ($tab->getItems() as $item) {
                    if ($item instanceof ComponentContract) {
                        $fields = array_merge($fields, $item->extractFields());
                    } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::FORM)) {
                        $fields[] = $item;
                    }
                }
            }
        } else {
            $fields = $this->getVisibleFields();
        }

        return array_values(array_filter($fields, fn (FieldContract $f) => !$f->isReadOnly()));
    }
}
