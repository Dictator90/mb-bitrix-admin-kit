<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

/**
 * Minimal application double for page tests that need component output.
 *
 * Bitrix components are not rendered by the core test bootstrap, so this
 * double records every invocation and renders the public error-component
 * contract that CRUD pages rely on.
 */
final class ComponentApplicationStub
{
    /** @var list<array{name:string,template:string,params:array<string,mixed>}> */
    public array $components = [];

    /** @var list<string> */
    public array $titles = [];

    public function SetTitle(string $title): void
    {
        $this->titles[] = $title;
    }

    /** @param array<string,mixed> $params */
    public function IncludeComponent(string $name, string $template, array $params): void
    {
        $this->components[] = [
            'name' => $name,
            'template' => $template,
            'params' => $params,
        ];

        if ($name !== 'bitrix:ui.info.error') {
            return;
        }

        $title = htmlspecialchars((string)($params['TITLE'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">' . $title . '</span></div>';
    }

    public function StoreCookies(): void
    {
    }

    public function AddHeadScript(string $path): void
    {
    }

    public function SetAdditionalCSS(string $path): void
    {
    }
}
