<?php

namespace Andrey\PhpMig;
use PDO;

class NewDlDb extends Db
{

    private $stmt;
    public function init()
    {
        $this->stmt = $this->conn->prepare("SELECT * FROM customers_addresses 
         WHERE customer_id = :customerId AND type = 'billing' ORDER BY id DESC limit 1");

    }
    public function fetchBillingAddressFromNewDl($customerId): Address {
        $addr = new Address();
        $this->stmt->bindParam(':customerId', $customerId);
        $this->stmt->execute();
        $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $addr->customerId = $rows[0]['customer_id'];
            $addr->line1 = $rows[0]['address_line1'];
            $addr->line2 = $rows[0]['address_line2'];
            $addr->city = $rows[0]['city_name'];
            $addr->region = $rows[0]['subdivision_name'];
            $addr->country = $rows[0]['country_code'];
            $addr->postalCode = $rows[0]['postal_code'];
        }
        $addr->filledBy = 'new DL address book';
        return $addr;
    }
}