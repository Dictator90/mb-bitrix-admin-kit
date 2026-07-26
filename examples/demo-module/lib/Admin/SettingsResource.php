<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Number;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Textarea;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\SettingsTable;

/**
 * A copyable settings CRUD example.
 *
 * It demonstrates an ORM-backed list, form, detail page, filters, SidePanel,
 * row actions and safe bulk updates. It intentionally stores one setting per
 * row, unlike SettingsPage which demonstrates Bitrix Option storage.
 */
final class SettingsResource extends DataManagerResource
{
    protected string $title = 'Demo settings registry';

    public static function getSort(): int
    {
        return 320;
    }

    public static function getMenuIcon(): string
    {
        return 'sys_menu_icon';
    }

    public function dataManagerClass(): string
    {
        return SettingsTable::class;
    }

    public function useSidePanel(): bool
    {
        return true;
    }

    public function sidePanelWidth(): int
    {
        return 840;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Code', 'CODE'),
            Text::make('Name', 'NAME'),
            Select::make('Scope', 'SCOPE')->options($this->scopeOptions()),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N'),
            Number::make('Sort', 'SORT'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Code', 'CODE')
                ->required()
                ->maxLength(100)
                ->placeholder('for example: api.timeout'),
            Text::make('Name', 'NAME')
                ->required()
                ->maxLength(255)
                ->placeholder('Human-readable setting name'),
            Select::make('Scope', 'SCOPE')
                ->options($this->scopeOptions())
                ->default('general'),
            Textarea::make('Value', 'VALUE')
                ->rows(7)
                ->help('A plain value, JSON or another application-specific payload.'),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y'),
            Number::make('Sort', 'SORT')->min(0)->default(500),
        ];
    }

    public function detailFields(): iterable
    {
        return $this->formFields();
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Code', 'CODE')->contains(),
            TextFilter::make('Name', 'NAME')->contains(),
            SelectFilter::make('Scope', 'SCOPE')->options($this->scopeOptions())->exact(),
            SelectFilter::make('Active', 'ACTIVE')->options([
                'Y' => 'Yes',
                'N' => 'No',
            ])->exact(),
        ];
    }

    public function rowActions(): iterable
    {
        return [
            RowAction::view(),
            RowAction::edit(),
            RowAction::delete(),
        ];
    }

    public function bulkActions(): iterable
    {
        return [
            BulkAction::make('activate', 'Activate')->update(['ACTIVE' => 'Y']),
            BulkAction::make('deactivate', 'Deactivate')->update(['ACTIVE' => 'N']),
            BulkAction::delete(),
        ];
    }

    public function defaultSort(): array
    {
        return ['SORT' => 'ASC', 'ID' => 'DESC'];
    }

    public function beforeCreate(FormData $data, DbOperationContext $context): void
    {
        $data->set('SCOPE', (string)$data->get('SCOPE', 'general'));
        $data->set('ACTIVE', (string)$data->get('ACTIVE', 'Y'));
        $data->set('SORT', (int)$data->get('SORT', 500));
    }

    /** @return array<string,string> */
    private function scopeOptions(): array
    {
        return [
            'general' => 'General',
            'integration' => 'Integration',
            'notifications' => 'Notifications',
        ];
    }
}
