<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Traits;

use Closure;

trait HasReactivity
{
    /** @var array<string, Closure> field column => resolver fn($value, $allData): mixed */
    protected array $onChangeCallbacks = [];

    protected bool $reactive = false;

    /**
     * Declare that THIS field depends on one or more source columns.
     * When a source column changes, applyDependency() is called and the field HTML is re-rendered via AJAX.
     *
     * Usage:
     *   IblockElementSelect::make('Элемент', 'ELEMENT_ID')
     *       ->dependsOn('IBLOCK_ID')  // built-in behaviour in IblockElementSelect
     *
     *   Select::make('Подкатегория', 'SUB_ID')
     *       ->dependsOn('CAT_ID', function(FieldContract $field, mixed $val, array $data): void {
     *           // mutate $field in place (e.g. change options)
     *       })
     *
     * @param string|array $sourceColumns Column(s) that trigger the re-render
     * @param Closure|null $modifier fn(static $field, mixed $sourceValue, array $allData): void
     */
    public function dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static
    {
        foreach ((array)$sourceColumns as $col) {
            $this->dependsOnMap[$col] = $modifier;
        }

        return $this;
    }

    public function hasDependency(): bool
    {
        return !empty($this->dependsOnMap);
    }

    /** @return string[] list of source column names this field depends on */
    public function getDependsOn(): array
    {
        return array_keys($this->dependsOnMap);
    }

    /**
     * Apply all registered dependency modifiers using the provided form data.
     * Mutates this field in place (e.g. resets entities, changes options).
     */
    public function applyDependency(array $formData): static
    {
        foreach ($this->dependsOnMap as $col => $modifier) {
            if ($modifier !== null) {
                $modifier($this, $formData[$col] ?? null, $formData);
            }
        }

        return $this;
    }

    /** @var array<string, Closure|null> sourceColumn => modifier fn */
    protected array $dependsOnMap = [];

    /**
     * Register a dependency: when THIS field changes, resolver provides a new value for $targetColumn.
     *
     * Usage:
     *   Select::make('Category', 'CATEGORY_ID')
     *       ->onChange('SUBCATEGORY_ID', fn($categoryId) => SubcategoryTable::getOptions($categoryId))
     *
     * @param string $targetColumn Column whose value will be reloaded
     * @param Closure $resolver fn($value, array $allData): mixed  New value or options array
     */
    public function onChange(string $targetColumn, Closure $resolver): static
    {
        $this->onChangeCallbacks[$targetColumn] = $resolver;
        $this->reactive = true;

        return $this;
    }

    public function isReactive(): bool
    {
        return $this->reactive;
    }

    /** @return array<string, Closure> */
    public function getOnChangeCallbacks(): array
    {
        return $this->onChangeCallbacks;
    }

    /**
     * Resolve new values for dependent fields when this field's value changes.
     *
     * @param mixed $value New value of this field
     * @param array $allData All current form data
     * @return array            ['targetColumn' => newValue, ...]
     */
    public function resolveReactiveDependencies(mixed $value, array $allData = []): array
    {
        $result = [];
        foreach ($this->onChangeCallbacks as $targetColumn => $resolver) {
            $result[$targetColumn] = $resolver($value, $allData);
        }

        return $result;
    }

    /**
     * Returns JS attributes to attach to the rendered input so the form
     * knows this field is reactive and should call the AJAX endpoint.
     */
    public function getReactiveAttributes(string $ajaxUrl): string
    {
        if (!$this->reactive) {
            return '';
        }

        $targets = array_keys($this->onChangeCallbacks);
        $targetsJson = htmlspecialcharsbx(json_encode($targets));
        $column = htmlspecialcharsbx($this->getColumn());
        $url = htmlspecialcharsbx($ajaxUrl);

        return ' data-reactive="1" data-reactive-field="' . $column . '" data-reactive-targets="' . $targetsJson . '" data-reactive-url="' . $url . '"';
    }
}
