<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Component\Badge;
use MB\Bitrix\AdminKit\Component\Button;
use MB\Bitrix\AdminKit\Component\Heading;
use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Component\Layout\Column;
use MB\Bitrix\AdminKit\Component\Layout\Divider;
use MB\Bitrix\AdminKit\Component\Layout\Flex;
use MB\Bitrix\AdminKit\Component\Layout\Grid;
use MB\Bitrix\AdminKit\Component\Layout\LineBreak;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Component\Notification;
use MB\Bitrix\AdminKit\Component\SidePanel;
use MB\Bitrix\AdminKit\Component\Toolbar;
use MB\Bitrix\AdminKit\Field\Checkbox;
use MB\Bitrix\AdminKit\Field\Color;
use MB\Bitrix\AdminKit\Field\Date;
use MB\Bitrix\AdminKit\Field\DateTime;
use MB\Bitrix\AdminKit\Field\DialogSelect;
use MB\Bitrix\AdminKit\Field\Email;
use MB\Bitrix\AdminKit\Field\EntitySelect;
use MB\Bitrix\AdminKit\Field\File;
use MB\Bitrix\AdminKit\Field\Hidden;
use MB\Bitrix\AdminKit\Field\HtmlEditor;
use MB\Bitrix\AdminKit\Field\IblockElementSelect;
use MB\Bitrix\AdminKit\Field\IblockSectionSelect;
use MB\Bitrix\AdminKit\Field\IblockSelect;
use MB\Bitrix\AdminKit\Field\Image;
use MB\Bitrix\AdminKit\Field\Number;
use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Preview;
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Slug;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\TagSelect;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Textarea;
use MB\Bitrix\AdminKit\Field\UserSelect;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;

/**
 * Standalone showcase for AdminKit fields and UI/layout components.
 *
 * Copy into your module's lib/Admin/Pages/, adjust namespace and moduleId.
 * Options are stored in b_option with KIT_TEST_* prefix.
 */
final class AdminKitTestPage extends OptionsPage
{
    /** Bitrix module id for Option::get/set — must match your hosting module. */
    protected string $moduleId = 'vendor.demo';

    protected bool $multiSite = false;

    public static function getId(): string
    {
        return 'kit_test';
    }

    public static function getTitle(): string
    {
        return 'AdminKit showcase';
    }

    public static function getSort(): int
    {
        return 500;
    }

    public static function isVisibleInMenu(): bool
    {
        return true;
    }

    public function canView(PermissionContext $context): bool
    {
        global $USER;

        return is_object($USER) && method_exists($USER, 'IsAdmin') && $USER->IsAdmin();
    }

    public function fields(): iterable
    {
        return [
            Tabs::make([
                $this->uiComponentsTab(),
                $this->basicFieldsTab(),
                $this->choiceAndDatesTab(),
                $this->filesTab(),
                $this->selectorsTab(),
                $this->visibilityTab(),
            ])->remember(),
        ];
    }

    private function uiComponentsTab(): Tab
    {
        $sidePanelJs = SidePanel::open($this->adminPageUrl(static::getId()));

        $jsDemos = implode(' ', [
            Button::primary('Success notification', ['type' => 'button', 'onclick' => Notification::success('Saved!')]),
            Button::secondary('Error notification', ['type' => 'button', 'onclick' => Notification::error('Save failed')]),
            Button::secondary('Open SidePanel', ['type' => 'button', 'onclick' => $sidePanelJs]),
        ]);

        return Tab::make('UI components', [
            Alert::make(
                'CRUD grid, SidePanel forms, bulk actions and HasMany tables are tested on your module Resource pages '
                . '(index/form), not on OptionsPage.',
                Alert::INFO,
            ),
            Heading::make('AdminKit UI components', 2)->subtitle('Static blocks — not saved to options'),
            Alert::make('Operation completed successfully', Alert::SUCCESS),
            Alert::make('Critical error', Alert::DANGER),
            Alert::make('Warning', Alert::WARNING)->closable(),
            Alert::make('Information message', Alert::INFO),
            LineBreak::make(8),
            Flex::make([
                Badge::make('Success', Badge::SUCCESS),
                Badge::make('Danger', Badge::DANGER),
                Badge::make('Warning', Badge::WARNING),
                Badge::make('Info', Badge::INFO),
                Badge::make('Neutral', Badge::NEUTRAL),
                Badge::make('Y')->map(['Y' => Badge::SUCCESS, 'N' => Badge::DANGER])->pill(),
            ])->gap(12),
            Divider::make('Layout'),
            Grid::make([
                Column::make([
                    Box::make('Left column', [
                        Badge::make('Box + Column span 6'),
                    ]),
                ])->span(6),
                Column::make([
                    Box::make('Right column', [
                        Badge::make('Grid 12-column'),
                    ])->collapsible(),
                ])->span(6),
            ]),
            Box::make('JS snippets (Button, Notification, SidePanel, Toolbar)', [
                Alert::make($jsDemos)->html(),
            ]),
        ])->id('ui');
    }

    private function basicFieldsTab(): Tab
    {
        return Tab::make('Basic fields', [
            Grid::make([
                Column::make([
                    Text::make('Text', 'KIT_TEST_TEXT')
                        ->required()
                        ->placeholder('Enter text')
                        ->hint('Required text field')
                        ->default('demo'),
                    Slug::make('Slug', 'KIT_TEST_SLUG')->dependsOn('KIT_TEST_TEXT'),
                    Textarea::make('Textarea', 'KIT_TEST_TEXTAREA')->default("line 1\nline 2"),
                    Number::make('Number', 'KIT_TEST_NUMBER')->min(0)->max(100)->default(42),
                    Email::make('Email', 'KIT_TEST_EMAIL')->default('admin@example.com'),
                    Password::make('Password', 'KIT_TEST_PASSWORD')->private(),
                ])->span(6),
                Column::make([
                    Hidden::make('Hidden', 'KIT_TEST_HIDDEN')->default('hidden-value'),
                    Preview::make('Preview (read-only)', 'KIT_TEST_PREVIEW')
                        ->default('Sample')
                        ->badge('success'),
                    HtmlEditor::make('HtmlEditor', 'KIT_TEST_HTML')
                        ->default('<p>HTML <strong>content</strong></p>')
                        ->height(350)
                        ->editorPlaceholder('Enter HTML content...')
                        ->autoResize(true, 600),
                    Color::make('Color', 'KIT_TEST_COLOR')->default('#336699'),
                    Checkbox::make('Checkbox', 'KIT_TEST_CHECKBOX')->default('Y'),
                    Switcher::make('Switcher', 'KIT_TEST_SWITCHER')->values('Y', 'N')->default('Y'),
                ])->span(6),
            ]),
        ])->id('basic');
    }

    private function choiceAndDatesTab(): Tab
    {
        return Tab::make('Choice & dates', [
            Select::make('Select', 'KIT_TEST_SELECT')
                ->options([
                    'a' => 'Option A',
                    'b' => 'Option B',
                    'c' => 'Option C',
                ])
                ->default('a'),
            Date::make('Date', 'KIT_TEST_DATE'),
            DateTime::make('DateTime', 'KIT_TEST_DATETIME'),
        ])->id('dates');
    }

    private function filesTab(): Tab
    {
        return Tab::make('Files', [
            File::make('File', 'KIT_TEST_FILE'),
            Image::make('Image', 'KIT_TEST_IMAGE'),
        ])->id('files');
    }

    private function selectorsTab(): Tab
    {
        $items = [
            UserSelect::make('UserSelect', 'KIT_TEST_USER'),
            $this->buildRoleDialogSelect(),
            TagSelect::make('TagSelect (user entity)', 'KIT_TEST_TAGS')
                ->entity('user')
                ->multiple(),
            BelongsTo::make('BelongsTo', 'KIT_TEST_BELONGS_TO')
                ->options(static fn (): array => self::staticBelongsToOptions())
                ->asDialogSelector(),
            BelongsToMany::make('BelongsToMany', 'KIT_TEST_BELONGS_TO_MANY')
                ->options(static fn (): array => self::staticBelongsToManyOptions())
                ->asDialogSelector(),
            EntitySelect::make('EntitySelect (user)', 'KIT_TEST_ENTITY')
                ->entityId('user'),
        ];

        $iblockId = $this->resolveFirstIblockId();
        if ($iblockId !== null) {
            $items[] = IblockSelect::make('IblockSelect', 'KIT_TEST_IB_IBLOCK', $iblockId);
            $items[] = IblockElementSelect::make('IblockElementSelect', 'KIT_TEST_IB_ELEMENT', $iblockId);
            $items[] = IblockSectionSelect::make('IblockSectionSelect', 'KIT_TEST_IB_SECTION', $iblockId);
        } else {
            $items[] = Alert::make(
                'iblock module is unavailable or no iblocks found — IblockSelect / IblockElementSelect / IblockSectionSelect skipped.',
                Alert::WARNING,
            );
        }

        return Tab::make('Selectors', $items)->id('selectors');
    }

    private function visibilityTab(): Tab
    {
        return Tab::make('Conditional visibility', [
            Select::make('Mode', 'KIT_TEST_VISIBILITY_MODE')
                ->options([
                    'simple' => 'Simple',
                    'advanced' => 'Advanced',
                ])
                ->default('simple'),
            Text::make('Advanced only', 'KIT_TEST_VISIBILITY_ADVANCED')
                ->visibleWhen('KIT_TEST_VISIBILITY_MODE', 'advanced'),
            Box::make('Advanced block', [
                Number::make('Extra number', 'KIT_TEST_VISIBILITY_NUMBER')->default(10),
            ])->visibleWhen('KIT_TEST_VISIBILITY_MODE', 'advanced'),
        ])->id('visibility');
    }

    private function buildRoleDialogSelect(): DialogSelect
    {
        return DialogSelect::make('DialogSelect (roles)', 'KIT_TEST_DIALOG')
            ->tabsContent([
                'roles' => [
                    'title' => 'Roles',
                    'items' => [
                        ['id' => 'admin', 'title' => 'Administrator'],
                        ['id' => 'editor', 'title' => 'Editor'],
                        ['id' => 'viewer', 'title' => 'Viewer'],
                    ],
                ],
            ]);
    }

    /** @return array<string, string> */
    private static function staticBelongsToOptions(): array
    {
        return [
            '1' => 'First option',
            '2' => 'Second option',
            '3' => 'Third option',
        ];
    }

    /** @return array<string, string> */
    private static function staticBelongsToManyOptions(): array
    {
        return [
            'tag-a' => 'Tag A',
            'tag-b' => 'Tag B',
            'tag-c' => 'Tag C',
        ];
    }

    private function resolveFirstIblockId(): ?int
    {
        if (!Loader::includeModule('iblock') || !class_exists(\Bitrix\Iblock\IblockTable::class)) {
            return null;
        }

        $row = \Bitrix\Iblock\IblockTable::query()
            ->setSelect(['ID'])
            ->setOrder(['ID' => 'ASC'])
            ->setLimit(1)
            ->fetch();

        return $row ? (int)$row['ID'] : null;
    }

    /**
     * @param array<string, scalar> $extra
     */
    private function adminPageUrl(string $pageId, array $extra = []): string
    {
        $uri = $this->request->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '';

        $query = array_merge(
            ['page' => $pageId],
            defined('LANGUAGE_ID') ? ['lang' => LANGUAGE_ID] : [],
            $extra,
        );

        return $path . '?' . http_build_query($query);
    }
}
