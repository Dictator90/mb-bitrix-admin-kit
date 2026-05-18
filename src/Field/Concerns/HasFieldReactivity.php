<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;

trait HasFieldReactivity
{
    /** @var array<string, Closure> */
    protected array $onChangeCallbacks = [];

    protected bool $reactive = false;

    /** @var array<string, Closure|null> */
    protected array $dependsOnMap = [];

    public function dependsOn(string|array $sourceColumns, ?Closure $modifier = null): static
    {
        foreach ((array)$sourceColumns as $column) {
            $this->dependsOnMap[$column] = $modifier;
        }

        return $this;
    }

    public function hasDependency(): bool
    {
        return $this->dependsOnMap !== [];
    }

    /** @return list<string> */
    public function getDependsOn(): array
    {
        return array_keys($this->dependsOnMap);
    }

    public function applyDependency(array $formData): static
    {
        foreach ($this->dependsOnMap as $column => $modifier) {
            if ($modifier !== null) {
                $modifier($this, $formData[$column] ?? null, $formData);
            }
        }

        return $this;
    }

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

    /** @return array<string,Closure> */
    public function getOnChangeCallbacks(): array
    {
        return $this->onChangeCallbacks;
    }

    /** @return array<string,mixed> */
    public function resolveReactiveDependencies(mixed $value, array $allData = []): array
    {
        $result = [];
        foreach ($this->onChangeCallbacks as $targetColumn => $resolver) {
            $result[$targetColumn] = $resolver($value, $allData);
        }

        return $result;
    }

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

    protected function renderReactiveAttrs(): string
    {
        if (!$this->reactive) {
            return '';
        }

        $targets = array_keys($this->onChangeCallbacks);
        $targetsJson = htmlspecialcharsbx(json_encode($targets));
        $column = htmlspecialcharsbx($this->column);

        return ' data-reactive="1" data-reactive-field="' . $column . '" data-reactive-targets="' . $targetsJson . '"';
    }
}
