<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Database\BulkResult;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Number;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Resource\CrudResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends CrudResource
{
    protected string $title = 'Demo products';

    public static function getSort(): int
    {
        return 200;
    }

    public static function getMenuIcon(): string
    {
        return 'iblock_menu_icon_types';
    }

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function useSidePanel(): bool
    {
        return true;
    }

    public function sidePanelWidth(): int
    {
        return 980;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Name', 'NAME')->displayUsing(static fn (mixed $value): string => (string)$value),
            Select::make('Type', 'TYPE')->options($this->typeOptions()),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N'),
            Number::make('Price', 'PRICE'),
            Text::make('Status label', 'STATUS_LABEL')
                ->computed(static fn (array $row): string => ($row['ACTIVE'] ?? 'N') === 'Y' ? 'Active' : 'Inactive'),
            Text::make('Price with VAT', 'PRICE_WITH_VAT'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Name', 'NAME')->required()->placeholder('Product name'),
            Select::make('Type', 'TYPE')->options($this->typeOptions())->default('simple'),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y'),
            Number::make('Sort', 'SORT')->default(500),
            Number::make('Price', 'PRICE'),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Name', 'NAME')->contains(),
            SelectFilter::make('Type', 'TYPE')->options($this->typeOptions())->exact(),
        ];
    }

    public function rowActions(): iterable
    {
        return [
            RowAction::edit(),
            RowAction::view(),
            RowAction::delete(),
        ];
    }

    public function bulkActions(): iterable
    {
        return [
            BulkAction::make('activate', 'Activate')->update(['ACTIVE' => 'Y']),
            BulkAction::make('deactivate', 'Deactivate')->update(['ACTIVE' => 'N']),
            BulkAction::make('mark_checked', 'Mark checked')
                ->handle(static function (array $ids): BulkResult {
                    return BulkResult::success($ids);
                }),
            BulkAction::delete(),
        ];
    }

    public function defaultSort(): array
    {
        return ['SORT' => 'ASC', 'ID' => 'DESC'];
    }

    public function indexRuntime(GridContext $context): array
    {
        return [
            new ExpressionField('PRICE_WITH_VAT', 'ROUND(%s * 1.2, 2)', ['PRICE']),
            // Example only: replace UserTable with your documented Bitrix provider/table when needed.
            new Reference('CREATOR', '\\Bitrix\\Main\\UserTable', ['=this.CREATED_BY' => 'ref.ID']),
        ];
    }

    public function indexSelect(GridContext $context): array
    {
        return ['PRICE_WITH_VAT'];
    }

    public function modifyIndexParams(array $params, GridContext $context): array
    {
        $params['filter']['!NAME'] = false;

        return $params;
    }

    protected function beforeCreate(array $data): array
    {
        $data['SORT'] = isset($data['SORT']) ? (int)$data['SORT'] : 500;

        return $data;
    }

    /** @return array<string,string> */
    private function typeOptions(): array
    {
        return [
            'simple' => 'Simple product',
            'service' => 'Service',
            'digital' => 'Digital product',
        ];
    }
}
