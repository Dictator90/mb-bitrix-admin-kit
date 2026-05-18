<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use Bitrix\Main\HttpRequest;
use MB\Bitrix\AdminKit\Action\AsyncAction;
use PHPUnit\Framework\TestCase;

final class AsyncActionTest extends TestCase
{
    public function testDispatchReturnsJsonSuccessPayload(): void
    {
        $action = new class ('ping', 'Ping') extends AsyncAction {
            public function handle(array $data): array
            {
                return ['pong' => true];
            }
        };

        $GLOBALS['MB_ADMIN_KIT_TEST_POST'] = ['ping' => '1'];
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = true;

        ob_start();
        $action->dispatch(new HttpRequest());
        $output = (string) ob_get_clean();

        self::assertStringContainsString('"status":"success"', str_replace(' ', '', $output));
        self::assertStringContainsString('"pong":true', str_replace(' ', '', $output));
    }

    public function testDispatchRejectsInvalidCsrf(): void
    {
        $previous = $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] ?? true;
        $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = false;

        try {
            $action = new class ('ping', 'Ping') extends AsyncAction {
                public function handle(array $data): array
                {
                    return [];
                }
            };

            ob_start();
            $action->dispatch(new HttpRequest());
            $output = (string) ob_get_clean();

            self::assertStringContainsString('"status":"error"', str_replace(' ', '', $output));
        } finally {
            $GLOBALS['MB_ADMIN_KIT_TEST_SESSID_VALID'] = $previous;
        }
    }
}
