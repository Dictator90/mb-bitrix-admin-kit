<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Component\Layout;

/**
 * Tabs container — wraps Tab instances and renders using the mb.ui.tabs JS extension.
 *
 * Tab cannot render standalone; it must be inside Tabs.
 *
 * Usage:
 *   Tabs::make([
 *       Tab::make('Основное', [
 *           Text::make('API Key', 'api_key'),
 *       ])->active(),
 *
 *       Tab::make('Дополнительно', [
 *           Switcher::make('Debug', 'debug'),
 *       ])->id('advanced'),
 *   ])
 */
class Tabs implements ComponentContract
{
    /** @var Tab[] */
    protected array $tabs = [];
    protected ?DataWrapper $item = null;
    protected PageType $pageType = PageType::FORM;
    /**
     * Bitrix JS extension to load. Default is the standard 'ui.tabs' (always available).
     * Switch to 'mb.ui.tabs' (from mb.core) to unlock icon + count badge support on headers.
     */
    protected string $extension = 'mb.ui.tabs';

    /** @param Tab[] $tabs */
    public function __construct(array $tabs = [])
    {
        $this->tabs = array_values(array_filter($tabs, fn ($t) => $t instanceof Tab));
    }

    /** @param Tab[] $tabs */
    public static function make(array $tabs = []): static
    {
        return new static($tabs);
    }

    public function withItem(?DataWrapper $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function withPageType(PageType $type): static
    {
        $this->pageType = $type;

        return $this;
    }

    /** Override the JS extension name (default: 'ui.tabs'). Use 'mb.ui.tabs' for icon/count support. */
    public function extension(string $name): static
    {
        $this->extension = $name;

        return $this;
    }

    public function render(): string
    {
        if (empty($this->tabs)) {
            return '';
        }

        Extension::load([$this->extension]);

        // Ensure at least one tab is active
        $hasActive = (bool)array_filter($this->tabs, fn (Tab $t) => $t->isActive());
        if (!$hasActive) {
            $this->tabs[0]->active();
        }

        $containerId = 'adminkit-tabs-' . bin2hex(random_bytes(5));
        $items = [];

        foreach ($this->tabs as $sort => $tab) {
            $items[] = [
                'id' => $tab->getId(),
                'sort' => $sort,
                'active' => $tab->isActive(),
                'head' => $tab->getHeadOptions(),
                'body' => $this->renderTabBody($tab),
            ];
        }

        // Split items: pass only head/id/sort/active to JS, inject body HTML directly
        // into the DOM after getContainer(). Reason: Tab.js lazy-loads body on 'onActive'
        // event, but for initially-active tabs #active starts as true so activate() skips
        // the emit — the body never loads. Direct DOM injection bypasses this entirely.
        $jsItems = [];
        $bodyInjects = [];

        foreach ($items as $item) {
            $bodyInjects[] = [
                'id' => $item['id'],
                'html' => $item['body'],
                'active' => $item['active'],
            ];
            unset($item['body']);
            $jsItems[] = $item;
        }

        $cid = htmlspecialchars($containerId, ENT_QUOTES);
        $ext = json_encode($this->extension, JSON_UNESCAPED_UNICODE);
        $jsItemsJson = json_encode($jsItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        $bodyInjectJson = json_encode($bodyInjects, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

        return <<<HTML
        <div id="{$cid}"></div>
        <script>
        BX.ready(function() {
            BX.Runtime.loadExtension({$ext}).then(function(m) {
                console.log(m);
                var tabs = new m.Tabs({ id: '{$cid}', items: {$jsItemsJson} })
                var container = tabs.getContainer();
                var bodies = {$bodyInjectJson};
                for (var i = 0; i < bodies.length; i++) {
                    var bodyData = container.querySelector('.ui-tabs__tab-body_inner[data-id="' + bodies[i].id + '"] .ui-tabs__tab-body_data');
                    if (bodyData) {
                        bodyData.innerHTML = bodies[i].html;
                        // innerHTML does not execute <script> tags — re-create each as a
                        // live script node so that field init scripts (Switcher, etc.) run.
                        bodyData.querySelectorAll('script').forEach(function(oldScript) {
                            var s = document.createElement('script');
                            s.textContent = oldScript.textContent;
                            oldScript.parentNode.replaceChild(s, oldScript);
                        });
                    }
                    // Tab.js bug: when active:true is passed, Tab#active starts as true,
                    // so activate() skips adding CSS classes (guards with #active !== true).
                    // We apply --body-active / --header-active directly to fix this.
                    if (bodies[i].active) {
                        var bodyInner = container.querySelector('.ui-tabs__tab-body_inner[data-id="' + bodies[i].id + '"]');
                        var header    = container.querySelector('[data-bx-name="' + bodies[i].id + '"]');
                        if (bodyInner) bodyInner.classList.add('--body-active');
                        if (header)    header.classList.add('--header-active');
                    }
                }
                BX.Dom.append(container, document.getElementById('{$cid}'));
                // Initialize ui.hint tooltips for any hints rendered inside tab bodies
                if (BX.UI && BX.UI.Hint) {
                    BX.UI.Hint.init(container);
                }
            });
        });
        </script>
        HTML;
    }

    /** @return FieldContract[] */
    public function extractFields(): array
    {
        $fields = [];

        foreach ($this->tabs as $tab) {
            foreach ($tab->getItems() as $item) {
                if ($item instanceof FieldContract) {
                    $fields[] = $item;
                } elseif ($item instanceof ComponentContract) {
                    $fields = array_merge($fields, $item->extractFields());
                }
            }
        }

        return $fields;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    // ── Internals ────────────────────────────────────────────────────────

    protected function renderTabBody(Tab $tab): string
    {
        ob_start();
        echo '<div class="ui-form">';

        foreach ($tab->getItems() as $child) {
            if ($child instanceof ComponentContract) {
                $inner = $child->withPageType($this->pageType)->withItem($this->item)->render();
                echo $this->wrapWithConditionalVisibility($child, $inner);
            } elseif ($child instanceof FieldContract && $child->isVisibleOn($this->pageType)) {
                echo $this->renderFieldRow($child);
            }
        }

        echo '</div>';

        return ob_get_clean();
    }

    protected function renderFieldRow(FieldContract $field): string
    {
        $value = $this->item?->get($field->getColumn()) ?? $field->getDefault();
        $column = htmlspecialcharsbx($field->getColumn());
        $label = htmlspecialcharsbx($field->getLabel());
        $required = $field->isRequired() ? ' <span class="ui-ctl-required">*</span>' : '';
        $hint = method_exists($field, 'renderHint') ? $field->renderHint() : '';

        $visibilityAttr = '';
        $extraClass = '';
        if (method_exists($field, 'getVisibleWhen') && ($rule = $field->getVisibleWhen()) !== null) {
            $visibilityAttr = ' data-visible-when="' . htmlspecialcharsbx(json_encode($rule)) . '"';
            $sourceVal = $this->item?->get($rule['column']) ?? null;
            if (!$this->checkVisibilityRule($rule, $sourceVal)) {
                $extraClass = ' adminkit-conditional-hidden';
            }
        }

        return '<div class="ui-form-row' . $extraClass . '" data-field-column="' . $column . '"' . $visibilityAttr . '>'
            . '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . $required . $hint . '</div></div>'
            . '<div class="ui-form-content">' . $field->renderFormField($value) . '</div>'
            . '</div>';
    }

    protected function wrapWithConditionalVisibility(ComponentContract $component, string $inner): string
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

    protected function checkVisibilityRule(array $rule, mixed $currentValue): bool
    {
        $str = (string)($currentValue ?? '');
        if (isset($rule['values'])) {
            return in_array($str, $rule['values'], true);
        }
        return $str === ($rule['value'] ?? '');
    }
}
