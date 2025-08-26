<?php

require_once 'vendor/autoload.php';

use Avalara\AvaTaxClient;
use Andrey\PhpMig\AppDb;
use Andrey\PhpMig\Address;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

$avalaraClient = new AvaTaxClient('barTestApp', 1, 'bar', getenv('APP_ENV'));
$avalaraClient->withSecurity(getenv('AVALARA_LOGIN'), getenv('AVALARA_PASSWORD'));

$appDbConn = new AppDb(getenv('APP_DB_DSN'), getenv('APP_DB_USER'), getenv('APP_DB_PASSWORD'));

foreach ($appDbConn->getRowIterator() as  $card) {
    if ($card['validate_status'] != AppDb::STATUS_WAIT) {
        continue;
    }
    if (empty($card['dl_address'])) {
        $status = AppDb::STATUS_ERR;
        $comment  =  'empty dl address';
    } else {
        $address = Address::fromJson($card['dl_address']);
        $res = $avalaraClient->resolveAddress($address->line1, $address->line2, $address->line3,
            $address->city, $address->region, $address->postalCode, $address->country);

        if (!empty($res->messages)) {
            $status = AppDb::STATUS_ERR;
            $comment  =  json_encode($res->messages);
        } else {
            $status = AppDb::STATUS_DONE;
            $comment = null;
        }
    }


    $appDbConn->updateValidateStatusAndComment($card['id'], $status, $comment);
}