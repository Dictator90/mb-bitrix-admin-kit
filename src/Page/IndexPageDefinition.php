<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Closure;
use InvalidArgumentException;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;

final class IndexPageDefinition implements IndexPageDefinitionContract
{
    private const REQUIRED_CALLBACKS = [
        'fields',
        'filters',
        'rowActions',
        'bulkActions',
        'defaultSort',
        'defaultFilter',
        'defaultSelect',
        'runtimeFields',
        'indexSelect',
        'indexFilter',
        'indexOrder',
        'indexRuntime',
        'beforeIndexQueryParams',
        'afterIndexRows',
        'mapIndexRow',
        'modifyIndexParams',
    ];

    /** @var array<string,Closure> */
    private array $callbacks;

    /** @param array<string,Closure> $callbacks */
    public function __construct(array $callbacks)
    {
        $callbacks['grouping'] ??= static fn (): null => null;
        $this->callbacks = $callbacks;
        foreach (self::REQUIRED_CALLBACKS as $name) {
            if (!isset($this->callbacks[$name])) {
                throw new InvalidArgumentException(sprintf('IndexPageDefinition callback "%s" is required.', $name));
            }
            if (!$this->callbacks[$name] instanceof Closure) {
                throw new InvalidArgumentException(sprintf('IndexPageDefinition callback "%s" must be a Closure.', $name));
            }
        }
    }

    /** @return iterable<FieldContract> */
    public function fields(): iterable
    {
        return ($this->callbacks['fields'])();
    }

    public function grouping(): ?IndexGrouping
    {
        $grouping = ($this->callbacks['grouping'])();

        return $grouping instanceof IndexGrouping ? $grouping : null;
    }

    /** @return iterable<FilterContract> */
    public function filters(): iterable
    {
        return ($this->callbacks['filters'])();
    }

    /** @return iterable<ActionContract> */
    public function rowActions(): iterable
    {
        return ($this->callbacks['rowActions'])();
    }

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable
    {
        return ($this->callbacks['bulkActions'])();
    }

    /** @return array<string,string> */
    public function defaultSort(): array
    {
        return ($this->callbacks['defaultSort'])();
    }

    /** @return array<string,mixed> */
    public function defaultFilter(): array
    {
        return ($this->callbacks['defaultFilter'])();
    }

    /** @return array<int,string> */
    public function defaultSelect(): array
    {
        return ($this->callbacks['defaultSelect'])();
    }

    /** @return array<int,mixed> */
    public function runtimeFields(): array
    {
        return ($this->callbacks['runtimeFields'])();
    }

    /** @return array<int,string> */
    public function indexSelect(GridContext $context): array
    {
        return ($this->callbacks['indexSelect'])($context);
    }

    /** @return array<string,mixed> */
    public function indexFilter(GridContext $context): array
    {
        return ($this->callbacks['indexFilter'])($context);
    }

    /** @return array<string,string> */
    public function indexOrder(GridContext $context): array
    {
        return ($this->callbacks['indexOrder'])($context);
    }

    /** @return array<int,mixed> */
    public function indexRuntime(GridContext $context): array
    {
        return ($this->callbacks['indexRuntime'])($context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function beforeIndexQueryParams(array $params, GridContext $context): array
    {
        return ($this->callbacks['beforeIndexQueryParams'])($params, $context);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function afterIndexRows(array $rows, GridContext $context): array
    {
        return ($this->callbacks['afterIndexRows'])($rows, $context);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public function mapIndexRow(array $row, GridContext $context): array
    {
        return ($this->callbacks['mapIndexRow'])($row, $context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function modifyIndexParams(array $params, GridContext $context): array
    {
        return ($this->callbacks['modifyIndexParams'])($params, $context);
    }
}
