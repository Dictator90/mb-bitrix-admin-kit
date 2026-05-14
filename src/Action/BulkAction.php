<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use Closure;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Support\Conditionable\ConditionTree;

class BulkAction implements \MB\Bitrix\AdminKit\Contracts\ActionContract
{
    protected string $id;
    protected string $label;
    protected bool $needsConfirm = false;
    protected ?string $confirmText = null;
    protected bool $danger = false;
    protected bool $allowRunByFilter = false;
    protected bool|Closure|ConditionTree|null $canSeeCondition = null;
    protected mixed $canRunCondition = null;
    protected ?Closure $handler = null;
    protected ?array $updateData = null;

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
        $this->updateData = $data;

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
        if ($ids === []) {
            return BulkResult::failure('Не выбраны элементы');
        }

        if (!$this->isRunnable($context)) {
            return BulkResult::failure('Bulk action is not allowed.');
        }

        if ($this->updateData !== null) {
            return (new BulkUpdateAction($this->id, $this->label))
                ->update($this->updateData)
                ->canRun($this->canRunCondition ?? true)
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
        return array_values(array_filter(
            AdminCollection::make($context->selectedIds)->all(),
            static fn(mixed $id): bool => $id !== null && $id !== ''
        ));
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
