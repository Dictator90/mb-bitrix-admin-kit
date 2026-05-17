<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use Bitrix\Main\Grid\Panel\Types;
use Closure;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Support\Conditionable\ConditionTree;

class BulkAction implements ActionContract, BulkPanelItemContract
{
    protected string $id;
    protected string $label;
    protected bool $needsConfirm = false;
    protected ?string $confirmText = null;
    protected bool $danger = false;
    protected bool $allowRunByFilter = false;
    protected string $group = 'default';
    protected ?string $groupLabel = null;
    protected int $sort = 100;
    protected ?string $icon = null;
    protected ?string $buttonClass = null;
    protected ?string $title = null;
    protected string $panelType = Types::BUTTON;
    protected array|Closure|null $customPanelItem = null;
    protected bool|Closure|ConditionTree|null $canSeeCondition = null;
    protected mixed $canRunCondition = null;
    protected ?Closure $handler = null;
    protected ?array $data = null;

    public function __construct(string $id, ?string $label = null)
    {
        $this->id = $id;
        $this->label = $label ?? $id;
    }

    public static function delete(): static
    {
        $action = new static('delete', 'Удалить выбранные');
        $action->needsConfirm = true;
        $action->confirmText = 'Вы уверены, что хотите удалить выбранные записи?';
        $action->danger = true;
        $action->group('danger', 'Удаление');
        $action->icon('ui-btn-icon-remove');
        $action->sort(100);

        return $action;
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

    public function group(string $group, ?string $label = null): static
    {
        $this->group = $group;
        $this->groupLabel = $label;

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

    public function danger(bool $danger = true): static
    {
        $this->danger = $danger;

        return $this;
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
        return $this->danger;
    }

    public function canRunByFilter(): bool
    {
        return $this->allowRunByFilter;
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

        $ids = $this->selectedIds($context);
        $guardErrors = (new QueryGuard())->validateBulkOperation($context);
        if ($guardErrors !== []) {
            return BulkResult::failure(implode(' ', $guardErrors));
        }

        if ($ids === []) {
            return BulkResult::failure('Не выбраны элементы');
        }

        if ($this->data !== null) {
            return (new BulkUpdateAction($this->id, $this->label))
                ->update($this->data)
                ->canRun($this->canRunCondition ?? true)
                ->allowRunByFilter($this->allowRunByFilter)
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

        if ($ids === [] && $this->canRunByFilter() && $context->filter !== null && $context->filter !== []) {
            $pk = $context->resource->getPrimaryKey();
            $rows = $context->resource->getList([
                'filter' => $context->filter,
                'select' => [$pk],
            ]);

            return AdminCollection::make($rows)->pluck($pk)->all();
        }

        if ($ids === [] && $this->canRunByFilter() && $context->filter === []) {
            // No filter means all records
            $pk = $context->resource->getPrimaryKey();
            $rows = $context->resource->getList([
                'select' => [$pk],
            ]);

            return AdminCollection::make($rows)->pluck($pk)->all();
        }

        return $ids;
    }

    protected function checkCsrf(): bool
    {
        return !function_exists('check_bitrix_sessid') || check_bitrix_sessid();
    }

    protected function normalizeCondition(bool|Closure|ConditionTree|string|null $condition, ?string $operator, mixed $value): bool|Closure|ConditionTree|null
    {
        if (is_string($condition) && $operator !== null) {
            return AdminCondition::tree()->where($condition, $operator, $value);
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
