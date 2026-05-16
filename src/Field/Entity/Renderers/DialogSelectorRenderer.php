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
     * @param list<string> $ids
     * @param array<string,string> $titles
     * @param array<int,array<string,mixed>> $entities
     */
    public function render(EntitySelectorConfig $config, array $ids, array $titles, array $entities): string
    {
        if (class_exists(Extension::class)) {
            Extension::load(['ui', 'mb.ui.dialog-selector']);
        }

        $baseName = htmlspecialchars($config->column, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $containerId = htmlspecialchars(AdminString::htmlId('adminkit_dialog_selector', $config->column . '_' . uniqid()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $dialogId = htmlspecialchars(AdminString::htmlId('adminkit_dialog', $config->column), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $readonlyJs = $config->readonly ? 'true' : 'false';
        $multipleJs = $config->multiple ? 'true' : 'false';

        $selectedItems = [];
        foreach ($ids as $id) {
            $selectedItems[] = (new EntitySelectorSelectedItem($config->entityId, $id, (string)($titles[$id] ?? $id)))->toArray();
        }

        $dialogOptions = [
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
            (new MB.UI.DialogSelector.DialogSelector({
                target: '#{$containerId}',
                name: '{$baseName}',
                multiple: {$multipleJs},
                readonly: {$readonlyJs},
                dialog: {$dialogJson}
            })).render();
        });
        </script>
        HTML;
    }
}
