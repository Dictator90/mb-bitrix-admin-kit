<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Closure;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorConfig;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorLabelResolver;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorProviderResolver;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorValueNormalizer;
use MB\Bitrix\AdminKit\Field\Entity\Renderers\DialogSelectorRenderer;
use MB\Bitrix\AdminKit\Field\Entity\Renderers\TagSelectorRenderer;

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

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        return $this->renderFormFieldWithTagSelector($value, $formData);
    }

    /** @param array<string,mixed> $formData */
    protected function renderFormFieldWithTagSelector(mixed $value = null, array $formData = []): string
    {
        $ids = $this->parseIds($this->resolveValue($value));
        $titles = $this->resolveTitles($ids);
        $entities = $this->entities !== [] ? $this->entities : [[
            'id' => $this->entityId,
            'options' => $this->entityOptions,
            'dynamicLoad' => true,
            'dynamicSearch' => true,
        ]];

        return (new TagSelectorRenderer())->render(
            config: $this->selectorConfig($formData),
            ids: $ids,
            titles: $titles,
            entities: $entities,
        );
    }

    protected function renderFormFieldWithDialogSelector(mixed $value = null): string
    {
        $ids = $this->parseIds($this->resolveValue($value));
        $titles = $this->resolveTitles($ids);
        $entities = $this->entities !== [] ? $this->entities : [[
            'id' => $this->entityId,
            'options' => $this->entityOptions,
            'dynamicLoad' => true,
            'dynamicSearch' => true,
        ]];
        return (new DialogSelectorRenderer())->render(
            config: $this->selectorConfig(),
            ids: $ids,
            titles: $titles,
            entities: $entities,
        );
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
        return (new EntitySelectorValueNormalizer())->normalizeValue($value, $this->multiple);
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

    protected function supportsInlineEdit(): bool
    {
        return false;
    }

    protected function parseIds(mixed $value): array
    {
        return (new EntitySelectorValueNormalizer())->parseIds($value);
    }

    /** @param string[] $ids @return array<string,string> */
    protected function resolveTitles(array $ids): array
    {
        return (new EntitySelectorLabelResolver())->resolve(
            ids: $ids,
            labelResolver: $this->labelResolver,
            providerClass: (new EntitySelectorProviderResolver())->resolveProviderClass($this->entityId, $this->entities),
            providerOptions: $this->entityOptions,
        );
    }

    /** @param string[] $ids */
    protected function renderHiddenInputs(array $ids, string $name, string $baseName): string
    {
        return (new TagSelectorRenderer())->renderHiddenInputs($ids, $name, $baseName, $this->multiple);
    }

    /** @param string[] $ids @return array<string,string> */
    protected function resolveTitlesFromProvider(array $ids): array
    {
        return (new EntitySelectorLabelResolver())->resolve(
            ids: $ids,
            labelResolver: null,
            providerClass: $this->resolveProviderClass(),
            providerOptions: $this->entityOptions,
        );
    }

    protected function resolveProviderClass(): ?string
    {
        return (new EntitySelectorProviderResolver())->resolveProviderClass($this->entityId, $this->entities);
    }

    /** @param array<string,mixed> $formData */
    protected function selectorConfig(array $formData = []): EntitySelectorConfig
    {
        return new EntitySelectorConfig(
            column: $this->column,
            entityId: $this->entityId,
            entityOptions: $this->entityOptions,
            entities: $this->entities,
            multiple: $this->multiple,
            readonly: $this->isReadOnlyFor($formData),
            placeholder: $this->placeholder,
        );
    }
}
