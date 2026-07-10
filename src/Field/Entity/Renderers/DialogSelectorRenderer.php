<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity\Renderers;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorConfig;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorSelectedItem;
use MB\Bitrix\AdminKit\Support\AdminString;

final class DialogSelectorRenderer
{
    /**
     * @param list<string>              $ids
     * @param array<string,string>     $titles
     * @param array<int,array<string,mixed>> $entities
     * @param array<string,mixed>|null $dialogOptionsOverride
     */
    public function render(
        EntitySelectorConfig $config,
        array $ids,
        array $titles,
        array $entities,
        ?array $dialogOptionsOverride = null,
    ): string {
        if (class_exists(Extension::class)) {
            Extension::load(['ui', 'mb.admin.kit']);
        }

        $baseName = htmlspecialchars($config->column, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $containerId = htmlspecialchars(AdminString::htmlId('adminkit_dialog_selector', $config->column . '_' . uniqid()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $dialogId = htmlspecialchars(AdminString::htmlId('adminkit_dialog', $config->column), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $readonlyJs = $config->readonly ? 'true' : 'false';
        $multipleJs = $config->multiple ? 'true' : 'false';
        $sortableJs = ($config->sortable && $config->multiple && !$config->readonly) ? 'true' : 'false';

        $selectedItems = [];
        foreach ($ids as $id) {
            $selectedItems[] = (new EntitySelectorSelectedItem($config->entityId, $id, (string)($titles[$id] ?? $id)))->toArray();
        }

        $dialogOptions = $dialogOptionsOverride ?? [
            'id' => $dialogId,
            'context' => $dialogId,
            'multiple' => $config->multiple,
            'dropdownMode' => true,
            'enableSearch' => true,
            'entities' => $entities,
            'selectedItems' => $selectedItems,
        ];
        $dialogJson = json_encode($dialogOptions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return <<<HTML
        <div id="{$containerId}" class="adminkit-entity-selector__container"></div>
        <script>
        BX.ready(function() {
            var run = function() {
                if (!MB.AdminKit || !MB.AdminKit.DialogSelector || !MB.AdminKit.DialogSelector.DialogSelector) {
                    return;
                }
                (new MB.AdminKit.DialogSelector.DialogSelector({
                    target: '#{$containerId}',
                    name: '{$baseName}',
                    multiple: {$multipleJs},
                    readonly: {$readonlyJs},
                    sortable: {$sortableJs},
                    dialog: {$dialogJson}
                })).render();
            };
            if (BX.Runtime && BX.Runtime.loadExtension) {
                BX.Runtime.loadExtension('mb.admin.kit').then(run).catch(run);
            } else {
                run();
            }
        });
        </script>
        HTML;
    }
}
