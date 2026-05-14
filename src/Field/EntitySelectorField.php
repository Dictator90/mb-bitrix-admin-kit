<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UI\Extension;
use Closure;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminString;

class EntitySelectorField extends Field
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

        if ($this->labelResolver === null) {
            $resolver = $this->defaultResolverForEntity($id);
            if ($resolver !== null) {
                $this->labelResolver = $resolver;
            }
        }

        return $this;
    }

    protected function defaultResolverForEntity(string $entityId): ?Closure
    {
        return match ($entityId) {
            'user', 'user-list' => static function (array $ids): array {
                if (!class_exists(\Bitrix\Main\UserTable::class)) {
                    return [];
                }
                $result = [];
                $rs = \Bitrix\Main\UserTable::getList([
                    'filter' => ['@ID' => $ids],
                    'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN'],
                ]);
                while ($row = $rs->fetch()) {
                    $display = trim(($row['NAME'] ?? '') . ' ' . ($row['LAST_NAME'] ?? ''));
                    $result[(string)$row['ID']] = $display !== '' ? $display : ($row['LOGIN'] ?? (string)$row['ID']);
                }
                return $result;
            },
            default => null,
        };
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
        return $this->previewValue($this->displayValue($value, $row, ['page' => 'index', 'field' => $this]));
    }

    public function renderDetail(mixed $value, array $row = []): string
    {
        return $this->previewValue($this->displayValue($value, $row, ['page' => 'detail', 'field' => $this]));
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
        if ($ids === [] || !$this->labelResolver instanceof Closure) {
            return [];
        }

        return array_map('strval', ($this->labelResolver)($ids));
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
}
