<?php

use Andrey\PhpMig\Logger;
use Andrey\PhpMig\LagacyDlDb;
use Andrey\PhpMig\NewDlDb;
use Andrey\PhpMig\DlDbPull;

require_once 'vendor/autoload.php';

$logger = new Logger('fetch-customer-from-dl');
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
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
$billingAddress = $dlDbPull->fetchBillingAddress('p1xdsbqj', 1191235);
$logger->log($billingAddress);
error_log(print_r($billingAddress, true));
error_log('done');