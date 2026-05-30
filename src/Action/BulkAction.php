<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use Bitrix\Main\Grid\Panel\Types;
use Bitrix\UI\Buttons\Color;
use Closure;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use MB\Support\Conditionable\ConditionTree;

class BulkAction implements ActionContract, BulkPanelItemContract
{
    protected string $id;
    protected string $label;
    protected bool $needsConfirm = false;
    protected ?string $confirmText = null;
    protected bool $allowRunByFilter = false;
    protected bool $allowRunWithoutFilter = false;
    protected string $group = 'default';
    protected ?string $groupLabel = null;
    protected int $groupSort = 100;
    protected int $sort = 100;
    protected ?string $icon = null;
    protected ?string $buttonClass = null;
    protected ?string $color = null;
    protected ?string $title = null;
    protected string $panelType = Types::BUTTON;
    protected array|Closure|null $customPanelItem = null;
    protected bool|Closure|ConditionTree|null $canSeeCondition = null;
    protected mixed $canRunCondition = null;
    protected ?Closure $handler = null;
    protected ?array $data = null;
    protected string $clientHandler = 'runBulkAction';


    public function __construct(string $id, ?string $label = null)
    {
        $this->id = $id;
        $this->label = $label ?? $id;
    }

    public static function delete(string $id = 'delete', ?string $label = null): MassDeleteAction
    {
        return MassDeleteAction::make($id, $label ?? LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BULK_DELETE_SELECTED', 'Delete'));
    }

    public static function make(string $id, ?string $label = null): static
    {
        return new static($id, $label);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function group(string $group, ?string $label = null, ?int $sort = null): static
    {
        $this->group = $group;
        $this->groupLabel = $label;

        if ($sort !== null) {
            $this->groupSort = $sort;
        }

        return $this;
    }

    public function groupSort(int $sort): static
    {
        $this->groupSort = $sort;

        return $this;
    }

    public function groupLabel(?string $label): static
    {
        $this->groupLabel = $label;

        return $this;
    }

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function buttonClass(?string $class): static
    {
        $this->buttonClass = $class;

        return $this;
    }

    public function class(string $class): static
    {
        $this->buttonClass = $class;

        return $this;
    }

    /**
     * Цвет кнопки — CSS-класс Bitrix UI (значение Bitrix\UI\Buttons\Color, напр. 'ui-btn-success').
     */
    public function color(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function primary(): static
    {
        return $this->color(Color::PRIMARY);
    }

    public function success(): static
    {
        return $this->color(Color::SUCCESS);
    }

    public function secondary(): static
    {
        return $this->color(Color::SECONDARY);
    }

    public function light(): static
    {
        return $this->color(Color::LIGHT_BORDER);
    }

    public function link(): static
    {
        return $this->color(Color::LINK);
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function panelType(string $type): static
    {
        $this->panelType = $type;

        return $this;
    }

    public function panelItem(array|Closure $item): static
    {
        $this->customPanelItem = $item;

        return $this;
    }

    public function confirm(?string $text = null): static
    {
        $this->needsConfirm = true;
        $this->confirmText = $text;

        return $this;
    }

    /** Семантический шорткат: красная кнопка (color = ui-btn-danger). */
    public function danger(bool $danger = true): static
    {
        return $this->color($danger ? Color::DANGER : null);
    }

    public function canSee(bool|Closure|ConditionTree|string $condition, ?string $operator = null, mixed $value = null): static
    {
        $this->canSeeCondition = $this->normalizeCondition($condition, $operator, $value);

        return $this;
    }

    public function canRun(bool|Closure|ConditionTree|string $condition, ?string $operator = null, mixed $value = null): static
    {
        $this->canRunCondition = $this->normalizeCondition($condition, $operator, $value);

        return $this;
    }

    public function handle(Closure $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Backward-compatible alias for callback bulk actions.
     */
    public function executeUsing(Closure $handler): static
    {
        return $this->handle($handler);
    }

    /**
     * Selects the JavaScript function from the mb.admin.kit GridBulkActions namespace.
     */
    public function clientHandler(string $handler): static
    {
        $handler = trim($handler);
        if ($handler !== '') {
            $this->clientHandler = $handler;
        }

        return $this;
    }

    public function getClientHandler(): string
    {
        return $this->clientHandler;
    }

    /** @param array<string,mixed> $data */
    public function update(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function allowRunByFilter(bool $allow = true): static
    {
        $this->allowRunByFilter = $allow;

        return $this;
    }

    public function allowRunWithoutFilter(bool $allow = true): static
    {
        $this->allowRunWithoutFilter = $allow;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isDelete(): bool
    {
        return $this->id === 'delete';
    }

    public function needsConfirm(): bool
    {
        return $this->needsConfirm;
    }

    public function getConfirmText(): ?string
    {
        return $this->confirmText;
    }

    public function isDanger(): bool
    {
        return $this->color === Color::DANGER;
    }

    public function canRunByFilter(): bool
    {
        return $this->allowRunByFilter;
    }

    public function canRunWithoutFilter(): bool
    {
        return $this->allowRunWithoutFilter;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getGroupLabel(): ?string
    {
        return $this->groupLabel;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getGroupSort(): int
    {
        return $this->groupSort;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getButtonClass(): ?string
    {
        return $this->buttonClass;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getPanelType(): string
    {
        return $this->panelType;
    }

    public function hasCustomPanelItem(): bool
    {
        return $this->customPanelItem !== null;
    }

    public function getCustomPanelItem(Grid $grid): ?array
    {
        if ($this->customPanelItem instanceof Closure) {
            return ($this->customPanelItem)($grid);
        }

        return $this->customPanelItem;
    }

    public function isVisible(BulkOperationContext|array $context = []): bool
    {
        return AdminCondition::evaluate($this->canSeeCondition, $this->conditionContext($context));
    }

    public function isRunnable(BulkOperationContext|array $context = [], array|object|null $item = null): bool
    {
        $conditionContext = $this->conditionContext($context, $item);

        return AdminCondition::evaluate($this->canRunCondition, $conditionContext);
    }

    public function execute(BulkOperationContext $context): BulkResult
    {
        if (!$this->checkCsrf()) {
            return BulkResult::failure('Invalid CSRF token.');
        }

        $guardErrors = (new QueryGuard())->validateBulkOperation($context);
        if ($guardErrors !== []) {
            return BulkResult::failure(implode(' ', $guardErrors));
        }

        if (!$this->isRunnable($context)) {
            return BulkResult::failure('Bulk action is not allowed.');
        }

        $ids = $this->selectedIds($context);

        if ($ids === []) {
            return BulkResult::failure(LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BULK_EMPTY_SELECTION', 'No items selected.'));
        }

        if ($this->data !== null) {
            return (new BulkUpdateAction($this->id, $this->label))
                ->update($this->data)
                ->canRun($this->canRunCondition ?? true)
                ->allowRunByFilter($this->allowRunByFilter)
                ->allowRunWithoutFilter($this->allowRunWithoutFilter)
                ->execute($context->with(['action' => $this]));
        }

        if ($this->handler !== null) {
            $result = ($this->handler)($ids, $context->with(['selectedIds' => $ids, 'action' => $this]));

            return $result instanceof BulkResult ? $result : BulkResult::failure('Bulk action handler must return BulkResult.');
        }

        return BulkResult::failure('Bulk action handler is not configured.');
    }

    /** @return array<int,mixed> */
    public function selectedIds(BulkOperationContext $context): array
    {
        $ids = array_values(array_filter(
            AdminCollection::make($context->selectedIds)->all(),
            static fn (mixed $id): bool => $id !== null && $id !== ''
        ));

        if ($ids !== [] || !$context->forAll || !$this->canRunByFilter()) {
            return $ids;
        }

        return $this->idsByFilter($context);
    }

    /** @return array<int,mixed> */
    protected function idsByFilter(BulkOperationContext $context): array
    {
        // TODO: switch to keyset/chunked loading for very large filtered operations.
        $pk = $context->resource->getPrimaryKey();
        $params = [
            'select' => [$pk],
        ];

        if ($context->filter !== []) {
            $params['filter'] = $context->filter;
        }

        $rows = $context->resource->getList($params);

        return AdminCollection::make($rows)->pluck($pk)->all();
    }

    protected function checkCsrf(): bool
    {
        return !function_exists('check_bitrix_sessid') || check_bitrix_sessid();
    }

    protected function normalizeCondition(bool|Closure|ConditionTree|string|null $condition, ?string $operator, mixed $value): bool|Closure|ConditionTree|null
    {
        if (is_string($condition) && $operator !== null) {
            if (!str_contains($condition, '.')) {
                $condition = 'item.' . $condition;
            }

            return function (array $context) use ($condition, $operator, $value): bool {
                $tree = AdminCondition::tree();
                $tree->context($context, 'default');
                foreach ($context as $alias => $val) {
                    if ($val === null || (!is_array($val) && !is_object($val) && !is_string($val))) {
                        continue;
                    }
                    $tree->context($val, is_string($alias) ? $alias : 'item_' . $alias);
                }
                $tree->where($condition, $operator, $value);
                return $tree->calculate()->result();
            };
        }

        return $condition;
    }

    /** @return array<string,mixed> */
    protected function conditionContext(BulkOperationContext|array $context = [], array|object|null $item = null): array
    {
        if (is_array($context)) {
            $data = $context;
        } else {
            $data = [
                'resource' => $context->resource,
                'action' => $context->action,
                'selectedIds' => $context->selectedIds,
                'userId' => $context->userId,
                'request' => $context->request,
                'filter' => $context->filter,
                'gridContext' => $context->gridContext,
                'forAll' => $context->forAll,
            ];
        }

        if ($item !== null) {
            $data['item'] = $item;
            if (is_array($item)) {
                $data += $item;
            }
        }

        return $data;
    }

}
