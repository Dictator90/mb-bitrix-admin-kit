<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;
use Bitrix\UI\EntitySelector\Item;
use Closure;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockElementListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockPropertyListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\UserGroupListProvider;
use MB\Bitrix\AdminKit\UI\EntitySelector\UserListProvider;

class EntitySelect extends Field
{
    protected string $entityId = 'user';

    /** @var array<string,mixed> */
    protected array $entityOptions = [];

    /** @var array<int,array<string,mixed>> */
    protected array $entities = [];

    protected ?Closure $labelResolver = null;

    public function entityId(string $entityId, array $options = []): static
    {
        $this->entityId = $entityId;
        $this->entityOptions = $options;
        $this->entities = [[
            'id' => $entityId,
            'options' => $options,
            'dynamicLoad' => true,
            'dynamicSearch' => true,
        ]];

        return $this;
    }

    public function entity(string $id, array $entityOptions = []): static
    {
        $this->entities[] = [
            'id' => $id,
            'options' => $entityOptions,
            'dynamicLoad' => true,
            'dynamicSearch' => true,
        ];

        if ($this->entityId === 'user' && $this->entities !== []) {
            $this->entityId = $id;
            $this->entityOptions = $entityOptions;
        }

        return $this;
    }

    public function resetEntities(): static
    {
        $this->entities = [];

        return $this;
    }

    public function resolveLabels(Closure $resolver): static
    {
        $this->labelResolver = $resolver;

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        return $this->renderFormFieldWithTagSelector($value);
    }

    protected function renderFormFieldWithTagSelector(mixed $value = null): string
    {
        if (class_exists(Extension::class)) {
            Extension::load('ui.entity-selector');
        }

        $ids = $this->parseIds($this->resolveValue($value));
        $titles = $this->resolveTitles($ids);
        $name = htmlspecialcharsbx($this->column . ($this->multiple ? '[]' : ''));
        $baseName = htmlspecialcharsbx($this->column);
        $containerId = htmlspecialcharsbx(AdminString::htmlId('adminkit_entity_selector', $this->column . '_' . uniqid()));
        $dialogId = htmlspecialcharsbx(AdminString::htmlId('adminkit_dialog', $this->column));
        $jsVariable = preg_replace('/[^A-Za-z0-9_]/', '_', AdminString::htmlId('adminkitSelector', $this->column . '_' . uniqid())) ?: 'adminkitSelector';
        $multipleJs = $this->multiple ? 'true' : 'false';
        $readonlyJs = $this->readonly ? 'true' : 'false';
        $hiddenInputs = $this->renderHiddenInputs($ids, $name, $baseName);
        $selectedItems = [];

        foreach ($ids as $id) {
            $selectedItems[] = [
                'entityId' => $this->entityId,
                'id' => $id,
                'title' => $titles[$id] ?? $id,
            ];
        }

        $entities = $this->entities !== [] ? $this->entities : [[
            'id' => $this->entityId,
            'options' => $this->entityOptions,
            'dynamicLoad' => true,
            'dynamicSearch' => true,
        ]];
        $entitiesJson = json_encode($entities, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $selectedJson = json_encode($selectedItems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $placeholder = htmlspecialcharsbx((string)($this->placeholder ?? ''));

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

    protected function renderFormFieldWithDialogSelector(mixed $value = null): string
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

        $entities = $this->entities !== [] ? $this->entities : [[
            'id' => $this->entityId,
            'options' => $this->entityOptions,
            'dynamicLoad' => true,
            'dynamicSearch' => true,
        ]];

        $dialogOptions = [
            'id' => $dialogId,
            'context' => $dialogId,
            'multiple' => $this->multiple,
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

    public function serializePostValue(mixed $value): mixed
    {
        $normalized = $this->normalize($value);
        if (is_array($normalized)) {
            return implode(',', $normalized);
        }

        return $normalized;
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->multiple) {
            if ($value === null || $value === '') {
                return [];
            }

            return array_values(array_filter(AdminCollection::make(is_array($value) ? $value : [$value])->all(), static fn ($id): bool => $id !== null && $id !== ''));
        }

        if (is_array($value)) {
            $first = reset($value);
            return $first === false || $first === '' ? null : $first;
        }

        return $value === '' ? null : $value;
    }

    public function renderIndex(mixed $value, array $row = []): string
    {
        if ($value instanceof FieldRenderContext) {
            $row = $value->row;
            $meta = array_merge($value->meta, ['page' => $value->page, 'field' => $this, 'context' => $value]);
            $value = $value->value;
        } else {
            $meta = ['page' => 'index', 'field' => $this];
        }

        return $this->previewValue($this->displayValue($value, $row, $meta));
    }

    public function renderDetail(mixed $value, array $row = []): string
    {
        if ($value instanceof FieldRenderContext) {
            $row = $value->row;
            $meta = array_merge($value->meta, ['page' => $value->page, 'field' => $this, 'context' => $value]);
            $value = $value->value;
        } else {
            $meta = ['page' => 'detail', 'field' => $this];
        }

        return $this->previewValue($this->displayValue($value, $row, $meta));
    }

    public function previewValue(mixed $value): string
    {
        $ids = $this->parseIds($value);
        if ($ids === []) {
            return '';
        }

        $titles = $this->resolveTitles($ids);
        $parts = [];
        foreach ($ids as $id) {
            $title = htmlspecialcharsbx((string)($titles[$id] ?? $id));
            $parts[] = '<span class="adminkit-entity-selector__chip">'
                . '<span class="adminkit-entity-selector__chip-title">' . $title . '</span>'
                . '</span>';
        }

        return implode(' ', $parts);
    }

    protected function parseIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', AdminCollection::make($value)->all()), static fn (string $id): bool => $id !== ''));
        }

        $str = (string)$value;
        if (str_contains($str, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $str)), static fn (string $id): bool => $id !== ''));
        }

        return [$str];
    }

    /** @param string[] $ids @return array<string,string> */
    protected function resolveTitles(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        if ($this->labelResolver instanceof Closure) {
            return array_map('strval', ($this->labelResolver)($ids));
        }

        return $this->resolveTitlesFromProvider($ids);
    }

    /** @param string[] $ids */
    protected function renderHiddenInputs(array $ids, string $name, string $baseName): string
    {
        if ($ids === []) {
            return $this->multiple ? '' : '<input type="hidden" name="' . $baseName . '" value="">';
        }

        $html = '';
        foreach ($ids as $id) {
            $html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialcharsbx($id) . '">';
        }

        return $html;
    }

    /** @param string[] $ids @return array<string,string> */
    protected function resolveTitlesFromProvider(array $ids): array
    {
        $providerClass = $this->resolveProviderClass();
        if ($providerClass === null || !class_exists($providerClass)) {
            return [];
        }

        $providerOptions = $this->entityOptions;
        $providerOptions['selected'] = $ids;
        $provider = new $providerClass($providerOptions);
        if (!method_exists($provider, 'getItems')) {
            return [];
        }

        $result = [];
        foreach ((array)$provider->getItems($ids) as $item) {
            if (!$item instanceof Item) {
                continue;
            }

            $id = (string)$item->getId();
            $title = (string)$item->getTitle();
            if ($id === '' || $title === '') {
                continue;
            }

            $result[$id] = $title;
        }

        return $result;
    }

    protected function resolveProviderClass(): ?string
    {
        $entityId = $this->entityId;
        if ($this->entities !== []) {
            $first = $this->entities[0] ?? null;
            if (is_array($first) && is_string($first['id'] ?? null) && $first['id'] !== '') {
                $entityId = $first['id'];
            }
        }

        return match ($entityId) {
            'user', 'user-list' => UserListProvider::class,
            'user-group', 'user-group-list' => UserGroupListProvider::class,
            'iblock', 'iblock-list' => IblockListProvider::class,
            'iblock-property', 'iblock-property-list' => IblockPropertyListProvider::class,
            'iblock-element', 'iblock-element-list' => IblockElementListProvider::class,
            default => null,
        };
    }
}
