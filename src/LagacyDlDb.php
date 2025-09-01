<?php

namespace Andrey\PhpMig;
use PDO;
use PDOStatement;

class LagacyDlDb extends Db
{
    private PDOStatement $byTokenStmt;
    private PDOStatement $byUserStmt;
    private PDOStatement $byLastOrderMetaStmt;

    public function init()
    {
        $this->byTokenStmt = $this->conn->prepare(<<<Q
    select * from wp_postmeta where
       post_id = (select MAX(post_id) from wp_postmeta where
           meta_key='_payment_method_token' and meta_value = :token)
            AND (meta_key like '_billing%' OR meta_key = '_customer_user')
Q);

        $this->byLastOrderMetaStmt = $this->conn->prepare(<<<Q
    select * from wp_postmeta where
       post_id = (select MAX(post_id) from wp_postmeta where
           meta_key='_customer_user' and meta_value = :customerId)
            AND (meta_key like '_billing%' OR meta_key = '_customer_user')
Q);

        $this->byUserStmt = $this->conn->prepare(<<<Q
    select * from wp_usermeta where
            meta_key like 'billing%' AND user_id = :customerId
Q);
    }
    function fetchBillingAddressByOrderMetaToken(string $ccToken): Address
    {
        $addr = new Address();
        $this->byTokenStmt->bindValue(':token', $ccToken);
        $this->byTokenStmt->execute();
        while ($row = $this->byTokenStmt->fetch()) {
            $this->fillOrderMetaValue($addr, $row);
        }
        $addr->filledBy = 'old token order';
        return $addr;
    }

    function fetchBillingAddressByLastOrderMeta(string $customerId): Address
    {
        $addr = new Address();
        $this->byLastOrderMetaStmt->bindValue(':customerId', $customerId);
        $this->byLastOrderMetaStmt->execute();
        while ($row = $this->byLastOrderMetaStmt->fetch()) {
            $this->fillOrderMetaValue($addr, $row);
        }
        $addr->filledBy = 'old last order';
        return $addr;
    }

    function fetchBillingAddressFromUserMeta(string $customerId): Address
    {
        $addr = new Address();
        $this->byUserStmt->bindValue(':customerId', $customerId);
        $this->byUserStmt->execute();
        $addr->customerId = $customerId;
        while ($row = $this->byUserStmt->fetch()) {
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
        $addr->filledBy = 'old user';
        return $addr;
    }

    private function fillOrderMetaValue(Address $addr, array $row)
    {
        switch ($row['meta_key']) {
            case '_customer_user': $addr->customerId = $row['meta_value']; break;
            case '_billing_address_1': $addr->line1 = $row['meta_value']; break;
            case '_billing_address_2': $addr->line2 = $row['meta_value']; break;
            case '_billing_address_3': $addr->line3 = $row['meta_value']; break;
            case '_billing_city': $addr->city = $row['meta_value']; break;
            case '_billing_state': $addr->region = $row['meta_value']; break;
            case '_billing_country': $addr->country = $row['meta_value']; break;
            case '_billing_postcode': $addr->postalCode = $row['meta_value']; break;
            case '_billing_company': $addr->company = $row['meta_value']; break;
        }
    }
}