<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Action\AsyncAction;
use MB\Bitrix\AdminKit\Tests\Support\BitrixContextTrait;
use PHPUnit\Framework\TestCase;

final class AsyncActionTest extends TestCase
{
    use BitrixContextTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setPostRequest(['ping' => '1']);
    }

    protected function tearDown(): void
    {
        $this->restoreRequest();
        parent::tearDown();
    }

    public function testDispatchReturnsJsonSuccessPayload(): void
    {
        $action = new class ('ping', 'Ping') extends AsyncAction {
            public function handle(array $data): array
            {
                return ['pong' => true];
            }
        };

        ob_start();
        $request = new HttpRequest(
            \Bitrix\Main\Application::getInstance()->getContext()->getServer(),
            ['sessid' => bitrix_sessid()],
            ['ping' => '1', 'sessid' => bitrix_sessid()],
            [],
            []
        );
        $action->dispatch($request);
        $output = (string) ob_get_clean();

        self::assertStringContainsString('"status":"success"', str_replace(' ', '', $output));
        self::assertStringContainsString('"pong":true', str_replace(' ', '', $output));
    }

    public function testDispatchRejectsInvalidCsrf(): void
    {
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $originalRequest = $context->getRequest();

        try {
            $invalidRequest = new HttpRequest($context->getServer(), ['sessid' => 'invalid'], ['sessid' => 'invalid'], [], []);
            $context->initialize($invalidRequest, $context->getResponse(), $context->getServer());

            $action = new class ('ping', 'Ping') extends AsyncAction {
                public function handle(array $data): array
                {
                    return [];
                }
            };

            ob_start();
            $request = new HttpRequest(new \Bitrix\Main\Server([]), [], [], [], []);
            $action->dispatch($request);
            $output = (string) ob_get_clean();

            self::assertStringContainsString('"status":"error"', str_replace(' ', '', $output));
        } finally {
            $context->initialize($originalRequest, $context->getResponse(), $context->getServer());
        }
    }
}
