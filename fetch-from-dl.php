<?php

require_once 'vendor/autoload.php';

use Andrey\PhpMig\AppDb;
use Andrey\PhpMig\Address;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT         => true
];

$legacyDbConn = new PDO(
    getenv('LEGACY_DL_DB_DSN'),
    getenv('LEGACY_DL_DB_USER'),
    getenv('LEGACY_DL_DB_PASSWORD'),
    $options
);

$appDbConn = new AppDb(getenv('APP_DB_DSN'), getenv('APP_DB_USER'), getenv('APP_DB_PASSWORD'));

$fetchByOrderMetaStmt = $legacyDbConn->prepare(<<<Q
    select * from wp_postmeta where
       post_id = (select MAX(post_id) from wp_postmeta where
           meta_key='_payment_method_token' and meta_value = :token)
            AND (meta_key like '_billing%' OR meta_key = '_customer_user')
Q);

$fetchByUserMetaStmt = $legacyDbConn->prepare(<<<Q
    select * from wp_usermeta where
            meta_key like 'billing%' AND user_id = :customerId
Q);



foreach ($appDbConn->getRowIterator() as  $card) {
    $billingAddress = fetchBillingAddressFromOrderMeta($fetchByOrderMetaStmt, $card['token']);
    if (!$billingAddress->isFull()) {
        $billingAddress = fetchBillingAddressFromUserMeta($fetchByUserMetaStmt, $card['customer_id']);
    }
    $appDbConn->updateDlAddress($card['id'], $billingAddress->toJson());
}

function fetchBillingAddressFromOrderMeta(PDOStatement $stmt, string $ccToken): Address
{
    $addr = new Address();
    $stmt->bindValue(':token', $ccToken);
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        switch ($row['meta_key']) {
            case '_customer_user:': $addr->customerId = $row['meta_value']; break;
            case '_billing_address_1': $addr->line1 = $row['meta_value']; break;
            case '_billing_address_2': $addr->line2 = $row['meta_value']; break;
            case '_billing_address_3': $addr->line3 = $row['meta_value']; break;
            case '_billing_city': $addr->city = $row['meta_value']; break;
            case '_billing_state': $addr->region = $row['meta_value']; break;
            case '_billing_country:': $addr->country = $row['meta_value']; break;
            case '_billing_postcode': $addr->postalCode = $row['meta_value']; break;
            case '_billing_company': $addr->company = $row['meta_value']; break;
        }
    }
    $addr->filledBy = 'order';
    return $addr;
}

function fetchBillingAddressFromUserMeta(PDOStatement $stmt, string $customerId): Address
{
    $addr = new Address();
    $stmt->bindValue(':customerId', $customerId);
    $stmt->execute();
    $addr->customerId = $customerId;
    while ($row = $stmt->fetch()) {
        switch ($row['meta_key']) {
            case 'billing_address_1': $addr->line1 = $row['meta_value']; break;
            case 'billing_address_2': $addr->line2 = $row['meta_value']; break;
            case 'billing_address_3': $addr->line3 = $row['meta_value']; break;
            case 'billing_city': $addr->city = $row['meta_value']; break;
            case 'billing_state': $addr->region = $row['meta_value']; break;
            case 'billing_country': $addr->country = $row['meta_value']; break;
            case 'billing_postcode': $addr->postalCode = $row['meta_value']; break;
            case 'billing_company': $addr->company = $row['meta_value']; break;
        }
    }
    $addr->filledBy = 'user';
    return $addr;
}

