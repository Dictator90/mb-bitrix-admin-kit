<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field\Entity;

use MB\Bitrix\AdminKit\Field\DialogSelect;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorConfig;
use MB\Bitrix\AdminKit\Field\Entity\Renderers\DialogSelectorRenderer;
use MB\Bitrix\AdminKit\Field\UserSelect;
use PHPUnit\Framework\TestCase;

final class DialogSelectorRendererTest extends TestCase
{
    public function testRendererUsesAdminKitExtensionWithoutHeavyDomLogic(): void
    {
        $html = (new DialogSelectorRenderer())->render(
            config: new EntitySelectorConfig(
                column: 'USER_ID',
                entityId: 'user',
                multiple: false,
                readonly: false,
                placeholder: null,
            ),
            ids: ['7'],
            titles: ['7' => 'Admin'],
            entities: [[
                'id' => 'user',
                'options' => [],
                'dynamicLoad' => true,
                'dynamicSearch' => true,
            ]],
        );

        self::assertStringContainsString("BX.Runtime.loadExtension('mb.admin.kit')", $html);
        self::assertStringContainsString('MB.AdminKit.DialogSelector.DialogSelector', $html);
        self::assertStringNotContainsString('mb.ui.dialog-selector', $html);
        self::assertStringNotContainsString('MB.UI.DialogSelector.DialogSelector', $html);
        self::assertStringNotContainsString('querySelectorAll', $html);
        self::assertStringNotContainsString('new m.DialogSelector', $html);
        self::assertStringNotContainsString('console.log', $html);
    }

    public function testDialogSelectPassesSelectedItemsInConfig(): void
    {
        $html = DialogSelect::make('Role', 'ROLE_ID')
            ->entityId('mbDialogEntity')
            ->items([
                ['id' => 'admin', 'entityId' => 'mbDialogEntity', 'title' => 'Admin', 'tabs' => ['roles']],
            ])
            ->tabs([['id' => 'roles', 'title' => 'Roles']])
            ->renderFormField('admin');

        self::assertStringContainsString('MB.AdminKit.DialogSelector.DialogSelector', $html);
        self::assertStringContainsString('admin', $html);
        self::assertStringContainsString('ROLE_ID', $html);
    }

    public function testUserSelectorUsesDialogSelectorRenderer(): void
    {
        $html = UserSelect::make('Responsible', 'RESPONSIBLE_ID')->renderForm(null);

        self::assertStringContainsString('MB.AdminKit.DialogSelector.DialogSelector', $html);
    }
}
