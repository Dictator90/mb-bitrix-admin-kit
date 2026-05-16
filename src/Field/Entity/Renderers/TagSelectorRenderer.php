<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity\Renderers;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorConfig;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorSelectedItem;
use MB\Bitrix\AdminKit\Support\AdminString;

final class TagSelectorRenderer
{
    /**
     * @param list<string> $ids
     * @param array<string,string> $titles
     * @param array<int,array<string,mixed>> $entities
     */
    public function render(EntitySelectorConfig $config, array $ids, array $titles, array $entities): string
    {
        if (class_exists(Extension::class)) {
            Extension::load('ui.entity-selector');
        }

        $name = htmlspecialcharsbx($config->column . ($config->multiple ? '[]' : ''));
        $baseName = htmlspecialcharsbx($config->column);
        $containerId = htmlspecialcharsbx(AdminString::htmlId('adminkit_entity_selector', $config->column . '_' . uniqid()));
        $dialogId = htmlspecialcharsbx(AdminString::htmlId('adminkit_dialog', $config->column));
        $jsVariable = preg_replace('/[^A-Za-z0-9_]/', '_', AdminString::htmlId('adminkitSelector', $config->column . '_' . uniqid())) ?: 'adminkitSelector';
        $multipleJs = $config->multiple ? 'true' : 'false';
        $readonlyJs = $config->readonly ? 'true' : 'false';
        $placeholder = htmlspecialcharsbx((string)($config->placeholder ?? ''));

        $selectedItems = [];
        foreach ($ids as $id) {
            $selectedItems[] = (new EntitySelectorSelectedItem($config->entityId, $id, (string)($titles[$id] ?? $id)))->toArray();
        }

        $hiddenInputs = $this->renderHiddenInputs($ids, $name, $baseName, $config->multiple);
        $entitiesJson = json_encode($entities, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $selectedJson = json_encode($selectedItems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return <<<HTML
        <div class="adminkit-entity-selector" data-field="{$baseName}">
            <div id="{$containerId}" class="adminkit-entity-selector__container"></div>
            <div class="adminkit-entity-selector__values" data-role="adminkit-entity-selector-values">{$hiddenInputs}</div>
        </div>
        <script>
        BX.ready(function() {
            var valueNode = document.querySelector('[data-field="{$baseName}"] [data-role="adminkit-entity-selector-values"]');
            var inputName = '{$name}';
            var renderValues = function(items) {
                if (!valueNode || {$readonlyJs}) { return; }
                var html = '';
                items.forEach(function(item) {
                    var id = BX.Text.encode(String(item.id));
                    html += '<input type="hidden" name="' + inputName + '" value="' + id + '">';
                });
                if (!{$multipleJs} && html === '') {
                    html = '<input type="hidden" name="{$baseName}" value="">';
                }
                valueNode.innerHTML = html;
            };
            var {$jsVariable} = new BX.UI.EntitySelector.TagSelector({
                id: '{$dialogId}',
                tags: {$selectedJson},
                dialogOptions: {
                    id: '{$dialogId}',
                    context: '{$dialogId}',
                    multiple: {$multipleJs},
                    dropdownMode: true,
                    enableSearch: true,
                    entities: {$entitiesJson},
                    selectedItems: {$selectedJson}
                },
                multiple: {$multipleJs},
                readonly: {$readonlyJs},
                placeholder: '{$placeholder}',
                events: {
                    onTagAdd: function(event) { renderValues(event.getTarget().getTags()); },
                    onTagRemove: function(event) { renderValues(event.getTarget().getTags()); }
                }
            });
            {$jsVariable}.renderTo(document.getElementById('{$containerId}'));
        });
        </script>
        HTML;
    }

    /** @param list<string> $ids */
    public function renderHiddenInputs(array $ids, string $name, string $baseName, bool $multiple): string
    {
        if ($ids === []) {
            return $multiple ? '' : '<input type="hidden" name="' . $baseName . '" value="">';
        }

        $html = '';
        foreach ($ids as $id) {
            $html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialcharsbx($id) . '">';
        }

        return $html;
    }
}
