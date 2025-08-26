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
    if (!empty($card['b3_address'])) {
        $address = Address::fromJson($card['b3_address']);
        if ($address->isFull()) {
            $appDbConn->updateUpdateStatusAndComment(
                $card['id'], AppDb::STATUS_DONE, 'b3_address looks like filled'
            );
        }
    }
}