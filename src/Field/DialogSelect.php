<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Field\Entity\Renderers\DialogSelectorRenderer;

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
        $ids = $this->parseIds($this->resolveValue($value));
        $titles = $this->resolveTitles($ids);
        $dialogId = 'adminkit_dialog_' . $this->column;

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

        return (new DialogSelectorRenderer())->render(
            config: $this->selectorConfig(),
            ids: $ids,
            titles: $titles,
            entities: [],
            dialogOptionsOverride: $dialogOptions,
        );
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
