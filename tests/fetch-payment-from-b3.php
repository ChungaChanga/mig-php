<?php

use Andrey\PhpMig\Logger;

require_once 'vendor/autoload.php';

$logger = new Logger('log/fetch-payment-from-b3');
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

$p = $gateway->paymentMethod()->find('cb917ek7');
$p = $p->toArray();
$logger->log($p);
error_log(print_r($p, 1));


