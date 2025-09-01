<?php

use Andrey\PhpMig\Logger;

require_once 'vendor/autoload.php';

$logger = new Logger('fetch-customer-from-b3');
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$config = new Braintree\Configuration([
    'environment' =>  getenv('APP_ENV'),
    'merchantId' => getenv('MERCHANT_ID'),
    'publicKey' => getenv('PUBLIC_KEY'),
    'privateKey' => getenv('PRIVATE_KEY')
]);
$config->timeout(60);
$gateway = new Braintree\Gateway($config);

$res = $gateway->address()->delete('dl-develop-9', 'fd');
$logger->log($res->success);


