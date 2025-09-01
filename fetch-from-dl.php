<?php

require_once 'vendor/autoload.php';

use Andrey\PhpMig\AppDb;
use Andrey\PhpMig\DlDbPull;
use Andrey\PhpMig\LagacyDlDb;
use Andrey\PhpMig\NewDlDb;
use Andrey\PhpMig\Logger;


if ($argc > 2) {
    $limit = $argv[1];
    $offset = $argv[2];
} else {
    die('укажите limit и offset');
}

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT         => true
];

$lagacyDlDb = new LagacyDlDb(
    getenv('LEGACY_DL_DB_DSN'),
    getenv('LEGACY_DL_DB_USER'),
    getenv('LEGACY_DL_DB_PASSWORD'),
    $options
);

$newDlDb = new NewDlDb(
    getenv('NEW_DL_DB_DSN'),
    getenv('NEW_DL_DB_USER'),
    getenv('NEW_DL_DB_PASSWORD'),
    $options
);

$dlDbPull = new DlDbPull($lagacyDlDb, $newDlDb);

$appDbConn = new AppDb(getenv('APP_DB_DSN'), getenv('APP_DB_USER'), getenv('APP_DB_PASSWORD'));

$logger = new Logger("from-dl-$limit-$offset.log");
error_log("limit $limit offset $offset");
foreach ($appDbConn->getRowIterator($limit, $offset) as  $card) {
    $billingAddress = $dlDbPull->fetchBillingAddress($card['token'], $card['customer_id']);
    if (is_null($billingAddress)) {
        $logger->log('address not found ' . $card['customer_id'] . ' - ' . $card['token']);
        continue;
    }
    $appDbConn->updateDlAddress($card['id'], $billingAddress->toJson());
}
error_log('done');



