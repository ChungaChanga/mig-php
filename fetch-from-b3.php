<?php

require_once 'vendor/autoload.php';

use Andrey\PhpMig\Logger;
use Andrey\PhpMig\Address;
use Andrey\PhpMig\AppDb;
use Andrey\PhpMig\Customer;
use Braintree\Customer as Braintree_Customer;
use Braintree\CreditCard as Braintree_CreditCard;

$logger = new Logger('dl1.log');
$loggerSkip = new Logger('skip.log');
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
/* redeclare vendor method for use page param
 * Braintree\Gateway
 * public function search($prefix, $page, $perPage = 10)
    {

        $s = new BraintreeCustomerFetcher(
            $this->_config->getEnvironment(),
            $this->_config->getPublicKey(),
            $this->_config->getPrivateKey(),
            $perPage
        );
        $res = $s->search($this->_config->serverName(), $this->_config->getMerchantId(), $prefix, $page, $perPage);
        return $res;
    }
 * */

$appDbConn = new AppDb(getenv('APP_DB_DSN'), getenv('APP_DB_USER'), getenv('APP_DB_PASSWORD'));


$page = 2620;
while (true) {
    $customers = $gateway->customer()->search(Customer::getPrefix(), $page);

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
    $logger->log("page: $page, count: " . count($customers));
    if (count($customers) < 50) {
        break;
    }
    $page++;
}

error_log('done');

