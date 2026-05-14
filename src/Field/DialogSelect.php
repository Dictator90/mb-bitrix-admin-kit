<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Support\AdminString;

class DialogSelect extends EntitySelect
{
    /** @var array<int,array<string,mixed>> */
    protected array $items = [];

    /** @var array<int,array<string,mixed>> */
    protected array $tabs = [];

    /** @param array<int,array<string,mixed>> $items */
    public function items(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /** @param array<int,array<string,mixed>> $tabs */
    public function tabs(array $tabs): static
    {
        $this->tabs = $tabs;

        return $this;
    }

    /** @param array<string,mixed> $item */
    public function addItem(array $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /** @param array<string,mixed> $tab */
    public function addTab(array $tab): static
    {
        $this->tabs[] = $tab;

        return $this;
    }

    /**
     * @param array<string,array{title:string,items:array<int,array<string,mixed>>}> $tabsContent
     */
    public function tabsContent(array $tabsContent): static
    {
        foreach ($tabsContent as $tabId => $tabData) {
            $this->addTab([
                'id' => (string)$tabId,
                'title' => (string)($tabData['title'] ?? $tabId),
            ]);

            foreach (($tabData['items'] ?? []) as $item) {
                $tabs = $item['tabs'] ?? [(string)$tabId];
                if (!is_array($tabs)) {
                    $tabs = [(string)$tabs];
                }

                $this->addItem([
                    'id' => $item['id'] ?? null,
                    'entityId' => $item['entityId'] ?? 'mbDialogEntity',
                    'title' => (string)($item['title'] ?? ($item['name'] ?? '')),
                    'subtitle' => (string)($item['subtitle'] ?? ''),
                    'tabs' => $tabs,
                ]);
            }
        }

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        if (class_exists(Extension::class)) {
            Extension::load(['ui', 'mb.ui.dialog-selector']);
        }

        $ids = $this->parseIds($this->resolveValue($value));
        $titles = $this->resolveTitles($ids);
        $baseName = htmlspecialchars($this->column, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $containerId = htmlspecialchars(
            AdminString::htmlId('adminkit_dialog_selector', $this->column . '_' . uniqid()),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $dialogId = htmlspecialchars(
            AdminString::htmlId('adminkit_dialog', $this->column),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $readonlyJs = $this->readonly ? 'true' : 'false';
        $multipleJs = $this->multiple ? 'true' : 'false';

        $selectedItems = [];
        foreach ($ids as $id) {
            $selectedItems[] = [
                'entityId' => $this->entityId,
                'id' => $id,
                'title' => $titles[$id] ?? $id,
            ];
        }

        $dialogOptions = [
            'id' => $dialogId,
            'context' => $dialogId,
            'multiple' => $this->multiple,
            'dropdownMode' => true,
            'enableSearch' => true,
            'selectedItems' => $selectedItems,
        ];

        if ($this->items !== []) {
            $dialogOptions['items'] = $this->preparedItems($ids);
            $dialogOptions['tabs'] = $this->tabs;
        } else {
            $dialogOptions['entities'] = $this->entities !== [] ? $this->entities : [[
                'id' => $this->entityId,
                'options' => $this->entityOptions,
                'dynamicLoad' => true,
                'dynamicSearch' => true,
            ]];
        }

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

    /** @param string[] $selectedIds @return array<int,array<string,mixed>> */
    protected function preparedItems(array $selectedIds): array
    {
        $selectedMap = array_fill_keys($selectedIds, true);
        $items = [];

        foreach ($this->items as $item) {
            $id = (string)($item['id'] ?? '');
            if ($id !== '' && isset($selectedMap[$id])) {
                $item['selected'] = true;
            }
            $items[] = $item;
        }

        return $items;
    }
}
