<?php

require_once 'vendor/autoload.php';

use Andrey\PhpMig\Address;
use Andrey\PhpMig\AppDb;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

$config = new Braintree\Configuration([
    'environment' =>  getenv('APP_ENV'),
    'merchantId' => getenv('MERCHANT_ID'),
    'publicKey' => getenv('PUBLIC_KEY'),
    'privateKey' => getenv('PRIVATE_KEY')
]);
$config->timeout(10);
$gateway = new Braintree\Gateway($config);

$appDbConn = new AppDb(getenv('APP_DB_DSN'), getenv('APP_DB_USER'), getenv('APP_DB_PASSWORD'));

foreach ($appDbConn->getRowIterator() as  $card) {
    if ($card['validate_status'] != AppDb::STATUS_DONE ||
        $card['update_b3_status'] == AppDb::STATUS_DONE) {
        continue;
    }
    $address = Address::fromJson($card['dl_address']);

    $result = $gateway->paymentMethod()->update(
        $address['token'], [
            'billingAddress' => [
                'streetAddress' => $address->line1,
                'locality' => $address->city,
                'region' => $address->region,
                'countryCodeAlpha2' => $address->country,
                'postalCode' => $address->postalCode,

                'company' => null,
                'countryCodeAlpha3' => null,
                'countryCodeNumeric' => null,
                'countryName' => null,
                'extendedAddress' => null,

                'options' => [
                    'updateExisting' => true
                ]
            ]
        ]
    );

    if ($result->success) {
        $status = AppDb::STATUS_DONE;
    } else {
        $status = AppDb::STATUS_ERR;
    }

    $appDbConn->updateUpdateStatusAndComment($card['id'], $status, $result->message);
}
