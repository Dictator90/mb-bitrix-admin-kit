<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED')) {
    define('B_PROLOG_INCLUDED', true);
}

putenv('MB_ADMIN_KIT_TESTING=1');

spl_autoload_register(function (string $class): void {
    $prefix = 'MB\\Bitrix\\AdminKit\\Tests\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
    $prefix = 'MB\\Bitrix\\AdminKit\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require_once __DIR__ . '/Relation/RelationTestFixtures.php';

// Initialize default Bitrix request context for tests
$app = \Bitrix\Main\Application::getInstance();
$server = new \Bitrix\Main\Server($_SERVER);
$sessid = bitrix_sessid();
$request = new \Bitrix\Main\HttpRequest($server, ['sessid' => $sessid], ['sessid' => $sessid], [], []);
$response = new \Bitrix\Main\HttpResponse();
$context = new \Bitrix\Main\Context($app);
$context->initialize($request, $response, $server);
$app->setContext($context);

if ($app->getSessionLocalStorageManager()) {
    $app->getSessionLocalStorageManager()->setUniqueId('cli_test_session');
}
