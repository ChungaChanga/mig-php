<?php

require_once 'vendor/autoload.php';

use Andrey\PhpMig\Logger;
use Andrey\PhpMig\Address;
use Andrey\PhpMig\AppDb;
use Andrey\PhpMig\Customer;
use Braintree\Customer as Braintree_Customer;
use Braintree\CustomerSearch;
use Braintree\CreditCard as Braintree_CreditCard;

$logger = new Logger('log/1.log');
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

$config = new Braintree\Configuration([
    'environment' =>  getenv('APP_ENV'),
    'merchantId' => getenv('MERCHANT_ID'),
    'publicKey' => getenv('PUBLIC_KEY'),
    'privateKey' => getenv('PRIVATE_KEY')
]);
$config->timeout(60);
$gateway = new Braintree\Gateway($config);

$appDbConn = new AppDb(getenv('APP_DB_DSN'), getenv('APP_DB_USER'), getenv('APP_DB_PASSWORD'));

$customers = $gateway->customer()->search([
    CustomerSearch::id()->startsWith(Customer::getPrefix()),
]);

foreach ($customers as  $customer) {
    /** @var Braintree_Customer $customer*/
    $customer = $customer->toArray();
    $customerId = Customer::b3ToDlId($customer['id']);
    $ccs = $customer['creditCards'];
    if (count($ccs) === 0) {
        continue;
    }
    foreach ($ccs as $cc) {
        try {
            /** @var Braintree_CreditCard $cc*/
            if (empty($cc->token)) {
                throw new Exception('Missing cc token' . print_r($cc, true));
            }
            $cc =  $cc->toArray();

            $addr = null;
            if (!empty($cc['billingAddress'])) {
                error_log(print_r($cc['billingAddress'], true));
                $addr = new Address();
                $addr->line1 = $cc['billingAddress']['streetAddress'];
                $addr->city = $cc['billingAddress']['locality'];
                $addr->region = $cc['billingAddress']['region'];
                $addr->country = $cc['billingAddress']['countryCodeAlpha2'];
                $addr->postalCode = $cc['billingAddress']['postalCode'];
                $addr = $addr->toJson();
            }
            $appDbConn->add($cc['token'], $customerId, $addr);
        } catch (Exception $e) {
            $logger->log($e->getMessage());
            $logger->log($e->getTrace());
        }
    }
}
error_log('done');

